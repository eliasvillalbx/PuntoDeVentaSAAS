<?php

namespace App\Http\Controllers;

use App\Models\CalendarEvent;
use App\Models\Empresa;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CalendarEventController extends Controller
{
    public function __construct()
    {
        // Pon tus middlewares si los usas
        //$this->middleware(['auth', 'verified']);
    }

    // === Helpers de permisos basados en roles ===

    protected function isSuperAdmin(User $user): bool
    {
        return $user->hasRole('superadmin');
    }

    protected function canManageEmpresaEvents(User $user): bool
    {
        return $user->hasRole('superadmin')
            || $user->hasRole('administrador_empresa')
            || $user->hasRole('gerente');
    }

    protected function canEditEvent(User $user, CalendarEvent $event): bool
    {
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        if ($this->canManageEmpresaEvents($user) && $user->id_empresa == $event->empresa_id) {
            return true;
        }

        return false;
    }

    // === Vista principal ===

    public function index(Request $request)
    {
        $user = Auth::user();
        $isSuperAdmin = $this->isSuperAdmin($user);

        if ($isSuperAdmin) {
            $empresaId = $request->integer('empresa_id') ?: ($user->id_empresa ?: null);
        } else {
            $empresaId = $user->id_empresa;
        }

        $empresa = $empresaId ? Empresa::find($empresaId) : null;

        $empresasLista = $isSuperAdmin
            ? Empresa::orderBy('nombre_comercial')->get()
            : ($empresa ? collect([$empresa]) : collect());

        // Usuarios de la empresa para las casillas
        $usuariosEmpresa = $empresaId
            ? User::where('id_empresa', $empresaId)->orderBy('nombre')->get()
            : collect();

        $canManage = $this->canManageEmpresaEvents($user);
        $empresaNombre = $empresa?->display_name ?? 'sin empresa asignada';

        return view('calendar.index', [
            'empresaId'       => $empresaId,
            'empresa'         => $empresa,
            'empresaNombre'   => $empresaNombre,
            'empresasLista'   => $empresasLista,
            'usuariosEmpresa' => $usuariosEmpresa,
            'isSuperAdmin'    => $isSuperAdmin,
            'canManage'       => $canManage,
        ]);
    }

    // === API: eventos para FullCalendar ===

    public function events(Request $request): JsonResponse
    {
        $user = Auth::user();
        $isSuperAdmin = $this->isSuperAdmin($user);

        try {
            if ($isSuperAdmin) {
                $empresaId = $request->integer('empresa_id')
                    ?: ($user->id_empresa ?: null);
            } else {
                $empresaId = $user->id_empresa;
            }

            if (!$empresaId) {
                return response()->json([
                    'ok'      => false,
                    'message' => 'No se pudo determinar la empresa para cargar los eventos.',
                    'events'  => [],
                ], 400);
            }

            $events = CalendarEvent::with(['empresa', 'user', 'assignedUsers'])
                ->where('empresa_id', $empresaId)
                ->orderBy('start', 'asc')
                ->get();

            $data = $events->map(function (CalendarEvent $ev) use ($user) {
                return [
                    'id'              => $ev->id,
                    'title'           => $ev->title,
                    'start'           => optional($ev->start)->toIso8601String(),
                    'end'             => optional($ev->end)->toIso8601String(),
                    'allDay'          => $ev->all_day,
                    'backgroundColor' => $ev->color ?: '#4f46e5',
                    'borderColor'     => $ev->color ?: '#4f46e5',
                    'extendedProps'   => [
                        'empresa_id'      => $ev->empresa_id,
                        'empresa_nombre'  => optional($ev->empresa)->display_name,
                        'user_id'         => $ev->user_id,
                        'user_nombre'     => $ev->user
                            ? trim(($ev->user->nombre ?? '') . ' ' . ($ev->user->apellido_paterno ?? ''))
                            : null,
                        'description'     => $ev->description,
                        'can_edit'        => $this->canEditEvent($user, $ev),

                        // usuarios asignados (tabla pivote)
                        'assigned_users'    => $ev->assignedUsers->map(function (User $u) {
                            return [
                                'id'     => $u->id,
                                'nombre' => trim(($u->nombre ?? '') . ' ' . ($u->apellido_paterno ?? '')),
                                'email'  => $u->email ?? null,
                            ];
                        })->values(),
                        'assigned_user_ids' => $ev->assignedUsers->pluck('id')->values(),
                    ],
                ];
            });

            return response()->json([
                'ok'     => true,
                'events' => $data,
            ]);
        } catch (\Throwable $e) {
            Log::error('Error al obtener eventos del calendario', [
                'user_id' => $user->id ?? null,
                'error'   => $e->getMessage(),
            ]);

            return response()->json([
                'ok'      => false,
                'message' => 'Ocurrió un error al cargar los eventos.',
                'events'  => [],
            ], 500);
        }
    }

    // === Crear evento ===

    public function store(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$this->canManageEmpresaEvents($user)) {
            return response()->json([
                'ok'      => false,
                'message' => 'No tienes permisos para crear eventos.',
            ], 403);
        }

        try {
            $isSuperAdmin = $this->isSuperAdmin($user);

            $validated = $request->validate([
                'title'             => ['required', 'string', 'max:255'],
                'description'       => ['nullable', 'string'],
                'start'             => ['required', 'date'],
                'end'               => ['nullable', 'date', 'after_or_equal:start'],
                'all_day'           => ['nullable', 'boolean'],
                'color'             => ['nullable', 'string', 'max:20'],
                'empresa_id'        => ['nullable', 'integer', 'exists:empresas,id'],

                // usuarios asignados (muchos a muchos)
                'assigned_user_ids'   => ['nullable', 'array'],
                'assigned_user_ids.*' => ['integer', 'exists:users,id'],
            ]);

            // Empresa del evento
            if ($isSuperAdmin && !empty($validated['empresa_id'])) {
                $empresaId = (int) $validated['empresa_id'];
            } else {
                $empresaId = $user->id_empresa;
            }

            if (!$empresaId) {
                return response()->json([
                    'ok'      => false,
                    'message' => 'No se pudo determinar la empresa del evento.',
                ], 400);
            }

            // Filtrar usuarios asignados que pertenezcan a esa empresa
            $assignedIds = collect($validated['assigned_user_ids'] ?? [])
                ->unique()
                ->values();

            if ($assignedIds->isNotEmpty()) {
                $validUserIds = User::whereIn('id', $assignedIds)
                    ->where('id_empresa', $empresaId)
                    ->pluck('id');

                $assignedIds = $assignedIds->intersect($validUserIds);
            }

            // Responsable = quien lo crea
            $event = CalendarEvent::create([
                'empresa_id'  => $empresaId,
                'user_id'     => $user->id,   // responsable
                'created_by'  => $user->id,   // quien lo registra
                'title'       => $validated['title'],
                'description' => $validated['description'] ?? null,
                'start'       => $validated['start'],
                'end'         => $validated['end'] ?? null,
                'all_day'     => $validated['all_day'] ?? false,
                'color'       => $validated['color'] ?? '#4f46e5',
            ]);

            // Sincronizar asignados en la tabla pivote
            if ($assignedIds->isNotEmpty()) {
                $event->assignedUsers()->sync($assignedIds->all());
            }

            return response()->json([
                'ok'    => true,
                'event' => $event,
            ]);
        } catch (\Throwable $e) {
            Log::error('Error al crear evento de calendario', [
                'user_id' => $user->id ?? null,
                'error'   => $e->getMessage(),
            ]);

            return response()->json([
                'ok'      => false,
                'message' => 'No se pudo crear el evento.',
            ], 500);
        }
    }

    // === Actualizar evento (datos / drag / resize) ===

    public function update(Request $request, CalendarEvent $event): JsonResponse
    {
        $user = Auth::user();

        if (!$this->canEditEvent($user, $event)) {
            return response()->json([
                'ok'      => false,
                'message' => 'No tienes permisos para modificar este evento.',
            ], 403);
        }

        try {
            $validated = $request->validate([
                'title'             => ['sometimes', 'string', 'max:255'],
                'description'       => ['sometimes', 'nullable', 'string'],
                'start'             => ['sometimes', 'date'],
                'end'               => ['sometimes', 'nullable', 'date', 'after_or_equal:start'],
                'all_day'           => ['sometimes', 'boolean'],
                'color'             => ['sometimes', 'nullable', 'string', 'max:20'],

                // usuarios asignados
                'assigned_user_ids'   => ['sometimes', 'array'],
                'assigned_user_ids.*' => ['integer', 'exists:users,id'],
            ]);

            // Actualizar campos simples
            $event->fill($validated);
            $event->save();

            // Si vienen usuarios asignados, actualizar pivot
            if (array_key_exists('assigned_user_ids', $validated)) {
                $assignedIds = collect($validated['assigned_user_ids'] ?? [])
                    ->unique()
                    ->values();

                if ($assignedIds->isNotEmpty()) {
                    $validUserIds = User::whereIn('id', $assignedIds)
                        ->where('id_empresa', $event->empresa_id)
                        ->pluck('id');

                    $assignedIds = $assignedIds->intersect($validUserIds);
                }

                $event->assignedUsers()->sync($assignedIds->all());
            }

            return response()->json([
                'ok'    => true,
                'event' => $event,
            ]);
        } catch (\Throwable $e) {
            Log::error('Error al actualizar evento de calendario', [
                'user_id'  => $user->id ?? null,
                'event_id' => $event->id ?? null,
                'error'    => $e->getMessage(),
            ]);

            return response()->json([
                'ok'      => false,
                'message' => 'No se pudo actualizar el evento.',
            ], 500);
        }
    }

    // === Eliminar evento ===

    public function destroy(CalendarEvent $event): JsonResponse
    {
        $user = Auth::user();

        if (!$this->canEditEvent($user, $event)) {
            return response()->json([
                'ok'      => false,
                'message' => 'No tienes permisos para eliminar este evento.',
            ], 403);
        }

        try {
            $event->delete();

            return response()->json([
                'ok'      => true,
                'message' => 'Evento eliminado correctamente.',
            ]);
        } catch (\Throwable $e) {
            Log::error('Error al eliminar evento de calendario', [
                'user_id'  => $user->id ?? null,
                'event_id' => $event->id ?? null,
                'error'    => $e->getMessage(),
            ]);

            return response()->json([
                'ok'      => false,
                'message' => 'No se pudo eliminar el evento.',
            ], 500);
        }
    }
}
