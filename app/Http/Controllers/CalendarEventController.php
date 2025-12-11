<?php

namespace App\Http\Controllers;

use App\Models\CalendarEvent;
use App\Models\Empresa;
use App\Models\User;
use App\Notifications\CalendarEventAssigned;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class CalendarEventController extends Controller
{
    public function __construct()
    {
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

    /**
     * Envía notificaciones a todos los usuarios asignados a un evento.
     */
    protected function notifyAssignedUsers(CalendarEvent $event, string $action): void
    {
        try {
            $event->loadMissing('assignedUsers', 'empresa', 'creator');

            if ($event->assignedUsers->isEmpty()) {
                Log::info('Evento sin usuarios asignados, no se envían notificaciones.', [
                    'event_id' => $event->id,
                    'action'   => $action,
                ]);
                return;
            }

            Log::info('Enviando notificaciones de evento de calendario.', [
                'event_id'  => $event->id,
                'action'    => $action,
                'user_ids'  => $event->assignedUsers->pluck('id')->all(),
            ]);

            Notification::send(
                $event->assignedUsers,
                new CalendarEventAssigned($event, $action)
            );
        } catch (\Throwable $e) {
            Log::warning('Error al enviar notificaciones de evento de calendario.', [
                'event_id' => $event->id ?? null,
                'action'   => $action,
                'error'    => $e->getMessage(),
            ]);
        }
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

        // Usuarios de la empresa para casillas del modal
        $usuariosEmpresa = $empresaId
            ? User::where('id_empresa', $empresaId)->orderBy('nombre')->get()
            : collect();

        $canManage     = $this->canManageEmpresaEvents($user);
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
        $user         = Auth::user();
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

                'assigned_user_ids'   => ['nullable', 'array'],
                'assigned_user_ids.*' => ['integer', 'exists:users,id'],
            ]);

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

            $assignedIds = collect($validated['assigned_user_ids'] ?? [])
                ->unique()
                ->values();

            if ($assignedIds->isNotEmpty()) {
                $validUserIds = User::whereIn('id', $assignedIds)
                    ->where('id_empresa', $empresaId)
                    ->pluck('id');

                $assignedIds = $assignedIds->intersect($validUserIds);
            }

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

            if ($assignedIds->isNotEmpty()) {
                $event->assignedUsers()->sync($assignedIds->all());
            }

            // 🔔 Notificar SIEMPRE que se crea (si hay asignados)
            $this->notifyAssignedUsers($event, 'created');

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

    // === Actualizar evento ===

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

                'assigned_user_ids'   => ['sometimes', 'array'],
                'assigned_user_ids.*' => ['integer', 'exists:users,id'],
            ]);

            $event->fill($validated);
            $event->save();

            // Actualizar asignados si los mandan
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

                $event->assignedUsers()->sync($assignedIds?->all() ?? []);
            }

            // 🔔 Notificar SIEMPRE que se actualiza (drag, resize, edición)
            $this->notifyAssignedUsers($event, 'updated');

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
            // 🔔 Notificar ANTES de borrar (para que siga teniendo assignedUsers)
            $this->notifyAssignedUsers($event, 'deleted');

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
