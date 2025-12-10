<?php

namespace App\Http\Controllers;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ChatController extends Controller
{
    public function __construct()
    {
        //$this->middleware(['auth', 'suscripcion.activa']);
    }

    /**
     * Pantalla principal tipo "Messenger".
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        if (!$user->id_empresa) {
            abort(403, 'El usuario no está asociado a ninguna empresa.');
        }

        try {
            $empresaId = (int) $user->id_empresa;

            // Conversaciones donde participa el usuario, de su empresa
            $conversations = ChatConversation::query()
                ->with(['users:id,nombre,apellido_paterno,apellido_materno'])
                ->where('empresa_id', $empresaId)
                ->where('is_active', true)
                ->whereHas('users', function ($q) use ($user) {
                    $q->where('users.id', $user->id);
                })
                ->orderByDesc('updated_at')
                ->get();

            // Para crear nuevas conversaciones, lista de usuarios de la misma empresa
            $empresaUsers = User::query()
                ->select('id', 'nombre', 'apellido_paterno', 'apellido_materno')
                ->where('id_empresa', $empresaId)
                ->where('id', '!=', $user->id)
                ->orderBy('nombre')
                ->get();

            return view('chat.index', [
                'conversations' => $conversations,
                'empresaUsers'  => $empresaUsers,
                'currentUser'   => $user,
            ]);
        } catch (\Throwable $e) {
            Log::error('Error al cargar chat.index', [
                'user_id' => $user->id ?? null,
                'error'   => $e->getMessage(),
            ]);

            return back()->with('error', 'No se pudo cargar el módulo de chat. Intenta de nuevo más tarde.');
        }
    }

    /**
     * Devuelve mensajes en JSON para una conversación (AJAX).
     */
    public function messages(Request $request, ChatConversation $conversation)
    {
        $user = Auth::user();

        if (!$this->usuarioPuedeVerConversacion($user, $conversation)) {
            return response()->json([
                'ok'      => false,
                'message' => 'No tienes permiso para ver esta conversación.',
            ], 403);
        }

        try {
            $messages = ChatMessage::query()
                ->with(['user:id,nombre,apellido_paterno,apellido_materno'])
                ->where('conversation_id', $conversation->id)
                ->orderBy('created_at')
                ->limit(100) // últimos 100, si quieres luego paginamos
                ->get()
                ->map(function (ChatMessage $m) use ($user) {
                    return [
                        'id'          => $m->id,
                        'message'     => $m->message,
                        'type'        => $m->type,
                        'file_path'   => $m->file_path,
                        'created_at'  => $m->created_at?->format('d/m/Y H:i'),
                        'sender_name' => $m->sender_name,
                        'is_me'       => $m->user_id === $user->id,
                    ];
                });

            return response()->json([
                'ok'       => true,
                'messages' => $messages,
            ]);
        } catch (\Throwable $e) {
            Log::error('Error al obtener mensajes', [
                'user_id'        => $user->id ?? null,
                'conversation_id'=> $conversation->id ?? null,
                'error'          => $e->getMessage(),
            ]);

            return response()->json([
                'ok'      => false,
                'message' => 'No se pudieron cargar los mensajes.',
            ], 500);
        }
    }

    /**
     * Envía un mensaje a una conversación (AJAX).
     */
    public function sendMessage(Request $request, ChatConversation $conversation)
    {
        $user = Auth::user();

        if (!$this->usuarioPuedeVerConversacion($user, $conversation)) {
            return response()->json([
                'ok'      => false,
                'message' => 'No tienes permiso para enviar mensajes en esta conversación.',
            ], 403);
        }

        $data = $request->validate([
            'message' => ['required', 'string', 'max:1000'],
        ]);

        try {
            $msg = null;

            DB::transaction(function () use (&$msg, $conversation, $user, $data) {
                $msg = ChatMessage::create([
                    'conversation_id' => $conversation->id,
                    'user_id'         => $user->id,
                    'message'         => $data['message'],
                    'type'            => 'text',
                ]);

                // Actualizamos updated_at de la conversación
                $conversation->touch();
            });

            return response()->json([
                'ok'      => true,
                'message' => [
                    'id'          => $msg->id,
                    'message'     => $msg->message,
                    'type'        => $msg->type,
                    'created_at'  => $msg->created_at?->format('d/m/Y H:i'),
                    'sender_name' => $msg->sender_name,
                    'is_me'       => true,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Error al enviar mensaje', [
                'user_id'        => $user->id ?? null,
                'conversation_id'=> $conversation->id ?? null,
                'error'          => $e->getMessage(),
            ]);

            return response()->json([
                'ok'      => false,
                'message' => 'No se pudo enviar el mensaje. Intenta de nuevo.',
            ], 500);
        }
    }

    /**
     * Crea una nueva conversación de grupo (o direct si solo hay 2 usuarios).
     */
    public function storeConversation(Request $request)
    {
        $user = Auth::user();

        if (!$user->id_empresa) {
            return back()->with('error', 'Tu usuario no está asociado a ninguna empresa.');
        }

        $data = $request->validate([
            'name'       => ['nullable', 'string', 'max:100'],
            'members'    => ['required', 'array', 'min:1'],
            'members.*'  => ['integer', 'exists:users,id'],
        ]);

        try {
            $empresaId = (int) $user->id_empresa;

            // Aseguramos que el mismo esté incluido
            if (!in_array($user->id, $data['members'], true)) {
                $data['members'][] = $user->id;
            }

            // Filtramos usuarios que no sean de la misma empresa
            $memberIds = User::query()
                ->whereIn('id', $data['members'])
                ->where('id_empresa', $empresaId)
                ->pluck('id')
                ->all();

            if (count($memberIds) < 2) {
                return back()->with('error', 'La conversación debe tener al menos 2 usuarios de la misma empresa.');
            }

            $type = count($memberIds) === 2 ? 'direct' : 'group';

            $conversation = null;

            DB::transaction(function () use (&$conversation, $empresaId, $user, $memberIds, $type, $data) {
                $conversation = ChatConversation::create([
                    'empresa_id' => $empresaId,
                    'type'       => $type,
                    'name'       => $type === 'group' ? ($data['name'] ?: 'Grupo sin nombre') : null,
                    'created_by' => $user->id,
                    'is_active'  => true,
                ]);

                $pivotData = [];
                $now = now();

                foreach ($memberIds as $memberId) {
                    $pivotData[$memberId] = [
                        'role'      => $memberId === $user->id ? 'owner' : 'member',
                        'joined_at' => $now,
                        'created_at'=> $now,
                        'updated_at'=> $now,
                    ];
                }

                $conversation->users()->attach($pivotData);
            });

            return redirect()
                ->route('chat.index', ['conversation' => $conversation->id])
                ->with('success', 'Conversación creada correctamente.');
        } catch (\Throwable $e) {
            Log::error('Error al crear conversación', [
                'user_id' => $user->id ?? null,
                'error'   => $e->getMessage(),
            ]);

            return back()->with('error', 'No se pudo crear la conversación. Intenta de nuevo.');
        }
    }

    /**
     * Valida si el usuario puede ver/enviar mensajes en la conversación.
     */
    protected function usuarioPuedeVerConversacion(User $user, ChatConversation $conversation): bool
    {
        // superadmin puede todo
        if (method_exists($user, 'hasRole') && $user->hasRole('superadmin')) {
            return true;
        }

        if (!$user->id_empresa || $conversation->empresa_id !== (int) $user->id_empresa) {
            return false;
        }

        return $conversation->users()
            ->where('users.id', $user->id)
            ->exists();
    }
}
