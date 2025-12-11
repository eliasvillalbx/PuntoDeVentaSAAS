<?php

namespace App\Http\Controllers;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\User;
use App\Models\Empresa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ChatController extends Controller
{
    /**
     * Vista principal del chat.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        try {
            // ¿Es Super Admin?
            $isSuperAdmin = method_exists($user, 'hasRole') && $user->hasRole('superadmin');

            // Empresa en contexto (por query ?empresa_id=...)
            $empresaId = $request->integer('empresa_id');

            if (!$empresaId) {
                // Si no viene en la URL:
                // - Usuario normal: su empresa
                // - SA: tomamos la primera empresa
                if (!$isSuperAdmin) {
                    $empresaId = $user->id_empresa ?? null;
                }

                if ($isSuperAdmin && !$empresaId) {
                    // Primera empresa disponible
                    $empresaId = Empresa::query()->value('id');
                }
            }

            $empresa = $empresaId ? Empresa::find($empresaId) : null;

            // Roles del usuario (Spatie)
            $roleNames = method_exists($user, 'getRoleNames')
                ? $user->getRoleNames()->toArray()
                : [];

            $roleLabel = count($roleNames)
                ? implode(', ', $roleNames)
                : 'Usuario';

            /**
             * Conversaciones visibles
             */
            $conversationsQuery = ChatConversation::query()
                ->with(['users' => function ($q) {
                    $q->select(
                        'users.id',
                        'users.nombre',
                        'users.apellido_paterno',
                        'users.apellido_materno',
                        'users.id_empresa'
                    );
                }])
                ->where('is_active', true);

            if (!$isSuperAdmin && $empresaId) {
                // Usuario normal -> conversaciones de su empresa donde participa
                $conversationsQuery
                    ->where('empresa_id', $empresaId)
                    ->whereHas('users', function ($q) use ($user) {
                        $q->where('users.id', $user->id);
                    });
            } elseif ($empresaId) {
                // SA -> todas las conversaciones de esa empresa
                $conversationsQuery->where('empresa_id', $empresaId);
            }

            $conversations = $conversationsQuery
                ->orderByDesc('updated_at')
                ->get();

            /**
             * Usuarios para el modal "Nueva conversación"
             * - SA: todos los usuarios de todas las empresas
             * - Usuario normal: sólo usuarios de su empresa
             *
             * OJO: la tabla empresas NO tiene "nombre", usamos nombre_comercial/razon_social
             */
            if ($isSuperAdmin) {
                $empresaUsers = User::query()
                    ->leftJoin('empresas', 'users.id_empresa', '=', 'empresas.id')
                    ->orderBy('empresas.nombre_comercial')   // <--- aquí
                    ->orderBy('users.nombre')
                    ->get([
                        'users.id',
                        'users.nombre',
                        'users.apellido_paterno',
                        'users.apellido_materno',
                        'users.id_empresa',
                        'empresas.nombre_comercial as empresa_nombre', // <--- aquí
                    ]);

                $empresasLista = Empresa::query()
                    ->orderBy('nombre_comercial') // <--- aquí
                    ->get(['id', 'razon_social', 'nombre_comercial']);
            } else {
                if ($empresaId) {
                    $empresaUsers = User::query()
                        ->leftJoin('empresas', 'users.id_empresa', '=', 'empresas.id')
                        ->where('users.id_empresa', $empresaId)
                        ->orderBy('users.nombre')
                        ->get([
                            'users.id',
                            'users.nombre',
                            'users.apellido_paterno',
                            'users.apellido_materno',
                            'users.id_empresa',
                            'empresas.nombre_comercial as empresa_nombre', // <--- aquí
                        ]);
                } else {
                    $empresaUsers = collect();
                }

                $empresasLista = collect();
            }

            return view('chat.index', [
                'currentUser'   => $user,
                'conversations' => $conversations,
                'empresaUsers'  => $empresaUsers,
                'empresaId'     => $empresaId,
                'empresa'       => $empresa,
                'isSuperAdmin'  => $isSuperAdmin,
                'roleLabel'     => $roleLabel,
                'empresasLista' => $empresasLista,
            ]);
        } catch (\Throwable $e) {
            Log::error('Error al cargar chat.index', [
                'user_id' => $user?->id,
                'error'   => $e->getMessage(),
            ]);

            return back()->with('error', 'No se pudo cargar el módulo de chat. Intenta de nuevo más tarde.');
        }
    }

    /**
     * Crear una nueva conversación (grupo o directo).
     */
    public function storeConversation(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name'        => ['nullable', 'string', 'max:255'],
            'members'     => ['required', 'array', 'min:1'],
            'members.*'   => ['integer', 'exists:users,id'],
            'empresa_id'  => ['nullable', 'integer', 'exists:empresas,id'],
        ], [
            'members.required' => 'Selecciona al menos un participante.',
            'members.min'      => 'Selecciona al menos un participante.',
        ]);

        try {
            $isSuperAdmin = method_exists($user, 'hasRole') && $user->hasRole('superadmin');

            // Determinar empresa de la conversación
            $empresaId = $user->id_empresa ?? null;

            // SA puede elegir la empresa del chat explícitamente
            if ($isSuperAdmin) {
                $empresaId = $validated['empresa_id']
                    ?? $request->integer('empresa_id')
                    ?? $empresaId;

                if (!$empresaId) {
                    $empresaId = Empresa::query()->value('id');
                }
            }

            if (!$empresaId) {
                throw new \RuntimeException('No se pudo determinar la empresa para la conversación.');
            }

            // Normalizar miembros
            $memberIds = collect($validated['members'])
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values();

            // Aseguramos que el usuario que crea esté incluido
            if (!$memberIds->contains($user->id)) {
                $memberIds->push($user->id);
            }

            // Tipo de chat
            $type = $memberIds->count() === 2 ? 'direct' : 'group';

            $conversation = null;

            DB::transaction(function () use (&$conversation, $empresaId, $type, $validated, $user, $memberIds) {
                // Reutilizar conversación directa si ya existe
                if ($type === 'direct') {
                    $otherId = $memberIds->first(fn ($id) => $id !== $user->id);

                    $conversation = ChatConversation::query()
                        ->where('empresa_id', $empresaId)
                        ->where('type', 'direct')
                        ->whereHas('users', fn ($q) => $q->where('users.id', $user->id))
                        ->whereHas('users', fn ($q) => $q->where('users.id', $otherId))
                        ->first();

                    if ($conversation) {
                        $conversation->touch();
                        return;
                    }
                }

                // Crear nueva conversación
                $conversation = ChatConversation::create([
                    'empresa_id' => $empresaId,
                    'type'       => $type,
                    'name'       => $validated['name'] ?? null,
                    'created_by' => $user->id,
                    'is_active'  => true,
                ]);

                // Pivot usuarios
                $pivotData = [];
                foreach ($memberIds as $id) {
                    $pivotData[$id] = [
                        'role'       => $id === $user->id ? 'owner' : 'member',
                        'joined_at'  => now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                $conversation->users()->sync($pivotData);
            });

            return redirect()
                ->route('chat.index', ['empresa_id' => $empresaId])
                ->with('success', 'Conversación creada correctamente.');
        } catch (\Throwable $e) {
            Log::error('Error al crear conversación de chat', [
                'user_id' => $user?->id,
                'error'   => $e->getMessage(),
            ]);

            return back()->with('error', 'No se pudo crear la conversación. Intenta de nuevo.');
        }
    }

    /**
     * Obtener mensajes de una conversación (JSON).
     */
    public function messages(ChatConversation $conversation, Request $request)
    {
        $user = Auth::user();

        try {
            $isSuperAdmin = method_exists($user, 'hasRole') && $user->hasRole('superadmin');

            $isMember = $conversation->users()
                ->where('users.id', $user->id)
                ->exists();

            if (!$isSuperAdmin && !$isMember) {
                return response()->json([
                    'ok'      => false,
                    'message' => 'No tienes permiso para ver esta conversación.',
                ], 403);
            }

            $messages = $conversation->messages()
                ->with('user:id,nombre,apellido_paterno,apellido_materno')
                ->orderBy('created_at')
                ->get()
                ->map(function (ChatMessage $msg) use ($user) {
                    $sender = $msg->user;
                    $name = $sender
                        ? trim(($sender->nombre ?? '') . ' ' . ($sender->apellido_paterno ?? ''))
                        : 'Usuario #' . $msg->user_id;

                    return [
                        'id'              => $msg->id,
                        'conversation_id' => $msg->conversation_id,
                        'user_id'         => $msg->user_id,
                        'message'         => $msg->message,
                        'type'            => $msg->type,
                        'file_path'       => $msg->file_path,
                        'created_at'      => $msg->created_at?->format('d/m/Y H:i'),
                        'sender_name'     => $name,
                        'is_me'           => $msg->user_id === $user->id,
                    ];
                });

            return response()->json([
                'ok'       => true,
                'messages' => $messages,
            ]);
        } catch (\Throwable $e) {
            Log::error('Error al obtener mensajes de conversación', [
                'user_id'         => $user?->id,
                'conversation_id' => $conversation->id ?? null,
                'error'           => $e->getMessage(),
            ]);

            return response()->json([
                'ok'      => false,
                'message' => 'No se pudieron cargar los mensajes.',
            ], 500);
        }
    }

    /**
     * Enviar mensaje a una conversación (JSON).
     */
    public function sendMessage(ChatConversation $conversation, Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'message' => ['required', 'string'],
        ], [
            'message.required' => 'Escribe un mensaje antes de enviar.',
        ]);

        try {
            $isSuperAdmin = method_exists($user, 'hasRole') && $user->hasRole('superadmin');

            $isMember = $conversation->users()
                ->where('users.id', $user->id)
                ->exists();

            if (!$isSuperAdmin && !$isMember) {
                return response()->json([
                    'ok'      => false,
                    'message' => 'No tienes permiso para enviar mensajes en esta conversación.',
                ], 403);
            }

            $msg = null;

            DB::transaction(function () use (&$msg, $conversation, $user, $validated) {
                $msg = ChatMessage::create([
                    'conversation_id' => $conversation->id,
                    'user_id'         => $user->id,
                    'message'         => $validated['message'],
                    'type'            => 'text',
                ]);

                $conversation->touch();
            });

            try {
                event(new \App\Events\ChatMessageCreated($msg));
            } catch (\Throwable $e) {
                Log::warning('No se pudo emitir evento ChatMessageCreated', [
                    'message_id' => $msg->id,
                    'error'      => $e->getMessage(),
                ]);
            }

            $payload = [
                'id'              => $msg->id,
                'conversation_id' => $msg->conversation_id,
                'user_id'         => $msg->user_id,
                'message'         => $msg->message,
                'type'            => $msg->type,
                'file_path'       => $msg->file_path,
                'created_at'      => $msg->created_at?->format('d/m/Y H:i'),
                'sender_name'     => trim(($user->nombre ?? '') . ' ' . ($user->apellido_paterno ?? '')) ?: $user->email,
                'is_me'           => true,
            ];

            return response()->json([
                'ok'      => true,
                'message' => $payload,
            ]);
        } catch (\Throwable $e) {
            Log::error('Error al enviar mensaje de chat', [
                'user_id'         => $user?->id,
                'conversation_id' => $conversation->id ?? null,
                'error'           => $e->getMessage(),
            ]);

            return response()->json([
                'ok'      => false,
                'message' => 'No se pudo enviar el mensaje.',
            ], 500);
        }
    }
}
