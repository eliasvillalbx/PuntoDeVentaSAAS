{{-- resources/views/chat/index.blade.php --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Chat empresarial
        </h2>
    </x-slot>

    <div
        x-data="chatPage({
            conversations: @js($conversations),
            currentUserId: {{ $currentUser->id }},
        })"
        x-init="init()"
        class="bg-white shadow rounded-lg overflow-hidden h-[70vh] flex"
    >
        {{-- Panel izquierdo: conversaciones --}}
        <aside class="w-1/3 border-r border-gray-200 flex flex-col">
            <div class="p-3 border-b border-gray-200 flex items-center justify-between">
                <h3 class="font-semibold text-gray-700">Conversaciones</h3>
                <button
                    type="button"
                    @click="openNewConversation = true"
                    class="inline-flex items-center text-sm px-2 py-1 rounded-md bg-sky-600 text-white hover:bg-sky-700"
                >
                    Nueva
                </button>
            </div>

            <div class="flex-1 overflow-y-auto">
                <template x-if="conversations.length === 0">
                    <p class="text-sm text-gray-500 p-4">
                        Aún no tienes conversaciones. Crea una con el botón "Nueva".
                    </p>
                </template>

                <template x-for="c in conversations" :key="c.id">
                    <button
                        type="button"
                        class="w-full text-left px-3 py-2 hover:bg-gray-100 flex items-center gap-2"
                        :class="selectedConversation && selectedConversation.id === c.id ? 'bg-gray-100' : ''"
                        @click="selectConversation(c)"
                    >
                        <div class="flex-1">
                            <div class="flex items-center justify-between">
                                <p class="font-medium text-sm text-gray-800" x-text="conversationTitle(c)"></p>
                                <span class="text-xs text-gray-400" x-text="c.updated_at_for_humans ?? ''"></span>
                            </div>
                            <p class="text-xs text-gray-500 truncate" x-text="c.last_message_preview ?? ''"></p>
                        </div>
                    </button>
                </template>
            </div>
        </aside>

        {{-- Panel derecho: mensajes --}}
        <section class="flex-1 flex flex-col">
            <template x-if="!selectedConversation">
                <div class="flex-1 flex items-center justify-center text-gray-400 text-sm">
                    Selecciona una conversación o crea una nueva.
                </div>
            </template>

            <template x-if="selectedConversation">
                <div class="flex-1 flex flex-col">
                    {{-- Header conversación --}}
                    <div class="border-b border-gray-200 px-4 py-3 flex items-center justify-between">
                        <div>
                            <h3 class="font-semibold text-gray-800" x-text="conversationTitle(selectedConversation)"></h3>
                            <p class="text-xs text-gray-500">
                                <span x-text="selectedConversation.type === 'direct' ? 'Chat directo' : 'Grupo empresarial'"></span>
                            </p>
                        </div>
                    </div>

                    {{-- Mensajes --}}
                    <div
                        class="flex-1 overflow-y-auto px-4 py-3 space-y-2 bg-gray-50"
                        id="chatMessagesContainer"
                    >
                        <template x-if="loadingMessages">
                            <p class="text-xs text-gray-500">Cargando mensajes...</p>
                        </template>

                        <template x-if="!loadingMessages && messages.length === 0">
                            <p class="text-xs text-gray-400">No hay mensajes aún. Escribe el primero.</p>
                        </template>

                        <template x-for="m in messages" :key="m.id">
                            <div class="flex" :class="m.is_me ? 'justify-end' : 'justify-start'">
                                <div
                                    class="max-w-xs px-3 py-2 rounded-lg text-sm"
                                    :class="m.is_me ? 'bg-sky-600 text-white' : 'bg-white text-gray-800 border border-gray-200'"
                                >
                                    <p class="font-semibold text-xs mb-0.5" x-text="!m.is_me ? m.sender_name : 'Tú'"></p>
                                    <p class="whitespace-pre-wrap" x-text="m.message"></p>
                                    <p class="text-[10px] mt-1 opacity-70 text-right" x-text="m.created_at"></p>
                                </div>
                            </div>
                        </template>
                    </div>

                    {{-- Error --}}
                    <template x-if="errorMessage">
                        <div class="px-4 py-2 bg-red-50 text-red-700 text-xs">
                            <span x-text="errorMessage"></span>
                        </div>
                    </template>

                    {{-- Input mensaje --}}
                    <form
                        @submit.prevent="sendMessage"
                        class="border-t border-gray-200 px-3 py-2 flex items-center gap-2 bg-white"
                    >
                        <input
                            type="text"
                            x-model="newMessage"
                            class="flex-1 text-sm border-gray-300 rounded-md shadow-sm focus:ring-sky-500 focus:border-sky-500"
                            placeholder="Escribe un mensaje..."
                        >

                        <button
                            type="submit"
                            :disabled="sending || !newMessage.trim()"
                            class="inline-flex items-center px-3 py-1.5 rounded-md text-sm font-medium text-white bg-sky-600 hover:bg-sky-700 disabled:opacity-60"
                        >
                            <span x-show="!sending">Enviar</span>
                            <span x-show="sending">Enviando...</span>
                        </button>
                    </form>
                </div>
            </template>
        </section>

        {{-- Modal simple para nueva conversación --}}
        <div
            x-show="openNewConversation"
            x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
        >
            <div
                class="bg-white rounded-lg shadow-lg w-full max-w-md p-4"
                @click.outside="openNewConversation = false"
            >
                <h3 class="font-semibold text-gray-800 mb-2">Nueva conversación</h3>
                <form method="POST" action="{{ route('chat.conversations.store') }}" class="space-y-3">
                    @csrf

                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">
                            Nombre del grupo (opcional, si seleccionas a más de 1 usuario)
                        </label>
                        <input
                            type="text"
                            name="name"
                            class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:ring-sky-500 focus:border-sky-500"
                            placeholder="Soporte, Ventas, General..."
                        >
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">
                            Miembros (usuarios de tu empresa)
                        </label>
                        <div class="max-h-48 overflow-y-auto border border-gray-200 rounded-md p-2 space-y-1">
                            @forelse ($empresaUsers as $u)
                                <label class="flex items-center gap-2 text-sm">
                                    <input type="checkbox" name="members[]" value="{{ $u->id }}" class="rounded border-gray-300">
                                    <span>{{ $u->nombre }} {{ $u->apellido_paterno }} {{ $u->apellido_materno }}</span>
                                </label>
                            @empty
                                <p class="text-xs text-gray-400">No hay otros usuarios en tu empresa.</p>
                            @endforelse
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button
                            type="button"
                            @click="openNewConversation = false"
                            class="px-3 py-1.5 text-sm rounded-md border border-gray-300 text-gray-700 hover:bg-gray-50"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            class="px-3 py-1.5 text-sm rounded-md bg-sky-600 text-white hover:bg-sky-700"
                        >
                            Crear
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Script Alpine para lógica básica (polling simple, sin websockets aún) --}}
    <script>
        function chatPage(props) {
            return {
                conversations: props.conversations || [],
                currentUserId: props.currentUserId,
                selectedConversation: null,
                messages: [],
                newMessage: '',
                loadingMessages: false,
                sending: false,
                errorMessage: '',
                openNewConversation: false,

                init() {
                    // Si viene una conversación ya seleccionada por query, la podrías leer aquí.
                    if (this.conversations.length > 0) {
                        this.selectConversation(this.conversations[0]);
                    }
                },

                conversationTitle(c) {
                    if (c.type === 'group') {
                        return c.name || 'Grupo sin nombre';
                    }
                    // direct: mostrar nombre del otro
                    if (!c.users) return 'Chat';
                    const other = c.users.find(u => u.id !== this.currentUserId);
                    if (!other) return 'Chat directo';
                    return `${other.nombre} ${other.apellido_paterno ?? ''}`.trim();
                },

                selectConversation(c) {
                    this.selectedConversation = c;
                    this.loadMessages();
                },

                async loadMessages() {
                    if (!this.selectedConversation) return;

                    this.loadingMessages = true;
                    this.errorMessage = '';

                    try {
                        const url = `/chat/conversations/${this.selectedConversation.id}/messages`;
                        const res = await fetch(url, {
                            headers: {
                                'Accept': 'application/json',
                            },
                        });

                        const data = await res.json();

                        if (!data.ok) {
                            this.errorMessage = data.message || 'Error al cargar mensajes.';
                            this.messages = [];
                        } else {
                            this.messages = data.messages || [];
                            this.$nextTick(() => this.scrollToBottom());
                        }
                    } catch (e) {
                        console.error(e);
                        this.errorMessage = 'No se pudieron cargar los mensajes.';
                        this.messages = [];
                    } finally {
                        this.loadingMessages = false;
                    }
                },

                async sendMessage() {
                    if (!this.selectedConversation || !this.newMessage.trim()) {
                        return;
                    }

                    this.sending = true;
                    this.errorMessage = '';

                    try {
                        const url = `/chat/conversations/${this.selectedConversation.id}/messages`;
                        const res = await fetch(url, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            },
                            body: JSON.stringify({
                                message: this.newMessage,
                            }),
                        });

                        const data = await res.json();

                        if (!data.ok) {
                            this.errorMessage = data.message || 'No se pudo enviar el mensaje.';
                        } else if (data.message) {
                            this.messages.push(data.message);
                            this.newMessage = '';
                            this.$nextTick(() => this.scrollToBottom());
                        }
                    } catch (e) {
                        console.error(e);
                        this.errorMessage = 'Error de conexión al enviar el mensaje.';
                    } finally {
                        this.sending = false;
                    }
                },

                scrollToBottom() {
                    const container = document.getElementById('chatMessagesContainer');
                    if (container) {
                        container.scrollTop = container.scrollHeight;
                    }
                },
            }
        }
    </script>
</x-app-layout>
