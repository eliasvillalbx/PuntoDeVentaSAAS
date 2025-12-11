<x-app-layout>
    @php
        /** @var \App\Models\User $authUser */
        $authUser = auth()->user();
        $empresaNombre = $empresa?->display_name ?? 'sin empresa asignada';

        $firstConversation = $conversations->first();
        $firstConversationTitle = null;

        if ($firstConversation) {
            if ($firstConversation->type === 'group') {
                $firstConversationTitle = $firstConversation->name ?: ('Grupo #' . $firstConversation->id);
            } else {
                $others = $firstConversation->users->where('id', '!=', $authUser->id);
                if ($others->count()) {
                    $firstConversationTitle = $others->map(function ($u) {
                        return trim(($u->nombre ?? '') . ' ' . ($u->apellido_paterno ?? ''));
                    })->implode(', ');
                } else {
                    $firstConversationTitle = 'Conversación #' . $firstConversation->id;
                }
            }
        }
    @endphp

    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <h2 class="font-semibold text-xl text-gray-900 leading-tight">
                {{ __('Chat empresarial') }}
            </h2>
            <p class="text-sm text-gray-600">
                Estás usando el chat como
                <span class="font-semibold text-indigo-600">{{ $roleLabel }}</span>
                en la empresa
                <span class="font-semibold text-indigo-700">{{ $empresaNombre }}</span>.
            </p>
        </div>
    </x-slot>

    <div class="py-6" x-data="chatPage()" x-init="init()">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            {{-- Mensajes de estado --}}
            @if (session('error'))
                <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-2 text-sm text-red-800">
                    {{ session('error') }}
                </div>
            @endif

            @if (session('success'))
                <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm text-emerald-800">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white border border-gray-200 shadow-sm sm:rounded-lg overflow-hidden">
                <div class="grid grid-cols-1 md:grid-cols-3 min-h-[480px]">
                    {{-- Columna izquierda: lista de conversaciones --}}
                    <div class="border-b md:border-b-0 md:border-r border-gray-200 flex flex-col bg-gray-50">
                        <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between gap-2 bg-white">
                            <div>
                                <h3 class="text-sm font-semibold text-gray-900">
                                    Conversaciones
                                </h3>
                                <p class="text-xs text-gray-500">
                                    Gestiona los chats con usuarios de tu empresa.
                                </p>
                            </div>
                            <button
                                type="button"
                                @click="openModal()"
                                class="inline-flex items-center gap-1 rounded-md bg-indigo-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-1 focus:ring-offset-white"
                            >
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none">
                                    <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                                Nueva
                            </button>
                        </div>

                        {{-- Selector de empresa para SUPERADMIN --}}
                        @if ($isSuperAdmin && $empresasLista->count())
                            <div class="px-4 pt-3 pb-2 border-b border-gray-200 bg-gray-50">
                                <form method="GET" action="{{ route('chat.index') }}" class="space-y-1.5">
                                    <label class="block text-[11px] font-medium text-gray-700">
                                        Empresa en contexto
                                    </label>
                                    <div class="flex items-center gap-2">
                                        <select
                                            name="empresa_id"
                                            class="flex-1 rounded-md border-gray-300 bg-white text-xs text-gray-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        >
                                            @foreach ($empresasLista as $e)
                                                <option
                                                    value="{{ $e->id }}"
                                                    @selected($empresa && $empresa->id === $e->id)
                                                >
                                                    {{ $e->display_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <button
                                            type="submit"
                                            class="rounded-md border border-gray-300 bg-white px-2.5 py-1 text-[11px] font-medium text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-1 focus:ring-offset-white"
                                        >
                                            Cambiar
                                        </button>
                                    </div>
                                    <p class="text-[11px] text-gray-500">
                                        Esto afecta las conversaciones que se muestran.
                                    </p>
                                </form>
                            </div>
                        @endif

                        {{-- Lista de conversaciones --}}
                        <div class="flex-1 overflow-y-auto p-3 space-y-1.5">
                            @forelse ($conversations as $conversation)
                                @php
                                    if ($conversation->type === 'group') {
                                        $convTitle = $conversation->name ?: ('Grupo #' . $conversation->id);
                                        $convSubtitle = 'Grupo de ' . $conversation->users->count() . ' participantes';
                                    } else {
                                        $others = $conversation->users->where('id', '!=', $authUser->id);
                                        if ($others->count()) {
                                            $convTitle = $others->map(function ($u) {
                                                return trim(($u->nombre ?? '') . ' ' . ($u->apellido_paterno ?? ''));
                                            })->implode(', ');
                                        } else {
                                            $convTitle = 'Conversación #' . $conversation->id;
                                        }
                                        $convSubtitle = 'Chat directo';
                                    }
                                @endphp

                                <button
                                    type="button"
                                    class="w-full text-left rounded-lg border border-transparent bg-white px-3 py-2.5 text-sm text-gray-900 hover:border-indigo-300 hover:bg-indigo-50 transition flex flex-col gap-0.5"
                                    :class="selectedConversationId === {{ $conversation->id }} ? 'border-indigo-400 bg-indigo-50' : ''"
                                    @click="selectedConversationLabel = @js($convTitle); selectConversation({{ $conversation->id }})"
                                >
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="font-semibold text-xs md:text-sm line-clamp-1">
                                            {{ $convTitle }}
                                        </span>
                                        <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-[10px] text-gray-700 border border-gray-200">
                                            {{ $conversation->type === 'group' ? 'Grupo' : 'Directo' }}
                                        </span>
                                    </div>
                                    <p class="text-[11px] text-gray-500 line-clamp-1">
                                        {{ $convSubtitle }}
                                    </p>
                                </button>
                            @empty
                                <p class="text-sm text-gray-500">
                                    Aún no tienes conversaciones. Usa el botón
                                    <span class="font-semibold">“Nueva”</span> para iniciar un chat.
                                </p>
                            @endforelse
                        </div>
                    </div>

                    {{-- Columna derecha: mensajes --}}
                    <div class="md:col-span-2 flex flex-col bg-gray-50">
                        {{-- Encabezado de la conversación --}}
                        <div class="px-4 py-3 border-b border-gray-200 bg-white flex items-center justify-between gap-2">
                            <div>
                                <h3 class="text-sm font-semibold text-gray-900">
                                    <span x-text="selectedConversationLabel || 'Selecciona una conversación'"></span>
                                </h3>
                                <p class="text-xs text-gray-500" x-show="selectedConversationId">
                                    Los mensajes se actualizan en tiempo real cuando se envían.
                                </p>
                                <p class="text-xs text-gray-500" x-show="!selectedConversationId">
                                    Elige una conversación en la lista de la izquierda o crea una nueva.
                                </p>
                            </div>
                        </div>

                        {{-- Contenido: mensajes --}}
                        <div class="flex-1 flex flex-col">
                            <div class="flex-1 overflow-y-auto px-4 py-3 space-y-2" x-show="selectedConversationId" x-cloak>
                                <template x-if="loadingMessages">
                                    <div class="text-center text-xs text-gray-500 py-4">
                                        Cargando mensajes...
                                    </div>
                                </template>

                                <template x-if="!loadingMessages && messages.length === 0">
                                    <div class="text-center text-xs text-gray-500 py-4">
                                        No hay mensajes aún. Escribe el primero.
                                    </div>
                                </template>

                                <template x-for="msg in messages" :key="msg.id">
                                    <div
                                        class="flex my-0.5"
                                        :class="msg.is_me ? 'justify-end' : 'justify-start'"
                                    >
                                        <div
                                            class="max-w-[80%] rounded-2xl px-3 py-2 text-xs md:text-sm shadow-sm"
                                            :class="msg.is_me ? 'bg-indigo-600 text-white' : 'bg-white text-gray-900 border border-gray-200'"
                                        >
                                            <p x-text="msg.message"></p>
                                            <p
                                                class="mt-1 text-[10px] opacity-75"
                                                :class="msg.is_me ? 'text-indigo-100' : 'text-gray-500'"
                                                x-text="(msg.sender_name || '') + (msg.created_at ? ' • ' + msg.created_at : '')"
                                            ></p>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            {{-- Estado de error --}}
                            <div
                                class="px-4 py-2 text-[11px] text-red-700 bg-red-50 border-t border-red-200"
                                x-show="error"
                                x-text="error"
                                x-cloak
                            ></div>

                            {{-- Formulario para enviar mensaje --}}
                            <form
                                x-show="selectedConversationId"
                                x-cloak
                                @submit.prevent="sendMessage"
                                class="border-t border-gray-200 bg-white px-4 py-3 flex items-end gap-2"
                            >
                                <div class="flex-1">
                                    <label class="sr-only">Mensaje</label>
                                    <textarea
                                        x-model="messageText"
                                        rows="2"
                                        class="block w-full rounded-md border-gray-300 bg-white text-sm text-gray-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        placeholder="Escribe un mensaje..."
                                    ></textarea>
                                </div>
                                <button
                                    type="submit"
                                    class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 disabled:opacity-40 disabled:cursor-not-allowed focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-1 focus:ring-offset-white"
                                    :disabled="sending || !messageText.trim().length"
                                >
                                    <svg
                                        class="h-4 w-4 mr-1"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        xmlns="http://www.w3.org/2000/svg"
                                    >
                                        <path
                                            d="M5 12L4.289 4.934C4.17324 3.77837 5.32611 2.96335 6.39315 3.42363L20 9L6.39315 14.5764C5.32611 15.0367 4.17324 14.2216 4.289 13.066L5 6L11 12L5 12Z"
                                            fill="currentColor"
                                        />
                                    </svg>
                                    <span x-text="sending ? 'Enviando...' : 'Enviar'"></span>
                                </button>
                            </form>

                            {{-- Placeholder cuando no hay conversación seleccionada --}}
                            <div
                                x-show="!selectedConversationId"
                                class="flex-1 flex items-center justify-center text-center text-sm text-gray-500 px-6"
                            >
                                Selecciona una conversación de la lista o crea una nueva para comenzar a chatear.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Modal: nueva conversación --}}
        <div
            x-show="modalOpen"
            x-cloak
            class="fixed inset-0 z-40 flex items-center justify-center bg-black/20"
        >
            <div
                class="w-full max-w-2xl rounded-xl border border-gray-200 bg-white shadow-xl flex flex-col max-h-[90vh]"
            >
                <div class="flex items-center justify-between border-b border-gray-200 px-4 py-3 bg-gray-50">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900">
                            Nueva conversación
                        </h3>
                        <p class="text-xs text-gray-500">
                            Selecciona participantes y, si es necesario, la empresa del chat.
                        </p>
                    </div>
                    <button
                        type="button"
                        class="rounded-full p-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-800"
                        @click="closeModal()"
                    >
                        <span class="sr-only">Cerrar</span>
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none">
                            <path d="M6 6L18 18M6 18L18 6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto px-4 py-3 space-y-4 text-sm text-gray-900">
                    {{-- Info rol/empresa --}}
                    <div class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-xs text-gray-700">
                        <p>
                            Estás creando la conversación como
                            <span class="font-semibold text-indigo-600">{{ $roleLabel }}</span>
                            en la empresa
                            <span class="font-semibold text-indigo-700">{{ $empresaNombre }}</span>.
                        </p>
                        @if ($isSuperAdmin)
                            <p class="mt-1 text-[11px] text-gray-500">
                                Como superadministrador puedes crear chats para cualquier empresa.
                            </p>
                        @endif
                    </div>

                    {{-- Empresa del chat (solo SA) --}}
                    @if ($isSuperAdmin)
                        <div class="space-y-1">
                            <label class="block text-xs font-medium text-gray-700">
                                Empresa de la conversación
                            </label>
                            <select
                                x-model="newConvEmpresaId"
                                class="block w-full rounded-md border-gray-300 bg-white text-xs text-gray-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                @foreach ($empresasLista as $e)
                                    <option value="{{ $e->id }}">
                                        {{ $e->display_name }}
                                    </option>
                                @endforeach
                            </select>
                            <p class="text-[11px] text-gray-500">
                                Los mensajes de este chat se asocian a la empresa seleccionada.
                            </p>
                        </div>
                    @endif

                    {{-- Nombre del grupo (opcional) --}}
                    <div class="space-y-1">
                        <label class="block text-xs font-medium text-gray-700">
                            Nombre del chat (opcional)
                        </label>
                        <input
                            type="text"
                            x-model="newConvName"
                            class="block w-full rounded-md border-gray-300 bg-white text-xs text-gray-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="Ej. Soporte ventas, Equipo sucursal centro..."
                        />
                        <p class="text-[11px] text-gray-500">
                            Si seleccionas solo 2 personas, se creará un chat directo aunque dejes este campo vacío.
                        </p>
                    </div>

                    {{-- Buscador de usuarios y lista con checkboxes --}}
                    <div class="space-y-2">
                        <div class="flex items-center justify-between gap-2">
                            <label class="block text-xs font-medium text-gray-700">
                                Participantes
                            </label>
                            <span class="text-[11px] text-gray-500">
                                Seleccionados:
                                <span class="font-semibold" x-text="selectedMembers.length"></span>
                            </span>
                        </div>

                        <div class="relative">
                            <input
                                type="text"
                                x-model="userSearch"
                                class="block w-full rounded-md border-gray-300 bg-white text-xs text-gray-900 pl-8 pr-2 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="Buscar por nombre o empresa..."
                            />
                            <span class="pointer-events-none absolute inset-y-0 left-2 flex items-center text-gray-400">
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none">
                                    <path d="M11 5a6 6 0 104.472 10.028l3.25 3.25" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                            </span>
                        </div>

                        <div class="mt-2 max-h-72 overflow-y-auto rounded-lg border border-gray-200 bg-white">
                            <template x-if="filteredUsers.length === 0">
                                <div class="px-3 py-2 text-[11px] text-gray-500">
                                    No se encontraron usuarios con ese criterio.
                                </div>
                            </template>

                            <template x-for="u in filteredUsers" :key="u.id">
                                <label
                                    class="flex items-start gap-2 px-3 py-2 text-xs text-gray-900 hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-b-0"
                                >
                                    <input
                                        type="checkbox"
                                        class="mt-0.5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                        :value="u.id"
                                        :checked="isMemberSelected(u.id)"
                                        @change="toggleMember(u.id)"
                                    />
                                    <div class="flex-1">
                                        <p class="font-semibold" x-text="`${u.nombre ?? ''} ${u.apellido_paterno ?? ''}`.trim()"></p>
                                        <p class="text-[11px] text-gray-500" x-text="u.empresa_nombre ? `Empresa: ${u.empresa_nombre}` : 'Empresa: n/d'"></p>
                                    </div>
                                </label>
                            </template>
                        </div>

                        <p class="text-[11px] text-gray-500">
                            Marca las casillas de los usuarios que quieras incluir en la conversación.
                        </p>
                    </div>
                </div>

                <div class="border-t border-gray-200 px-4 py-3 flex items-center justify-between gap-3 text-xs bg-gray-50">
                    <div
                        class="text-[11px] text-red-700"
                        x-show="error"
                        x-text="error"
                        x-cloak
                    ></div>
                    <div class="ml-auto flex items-center gap-2">
                        <button
                            type="button"
                            class="rounded-md border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-100"
                            @click="closeModal()"
                        >
                            Cancelar
                        </button>
                        <button
                            type="button"
                            class="rounded-md bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-500 disabled:opacity-40 disabled:cursor-not-allowed"
                            :disabled="selectedMembers.length === 0"
                            @click="createConversation()"
                        >
                            Crear conversación
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Script Alpine para manejar el chat --}}
    <script>
        function chatPage() {
            return {
                // Estado
                selectedConversationId: null,
                selectedConversationLabel: @json($firstConversationTitle),
                messages: [],
                loadingMessages: false,
                sending: false,
                error: null,
                messageText: '',

                modalOpen: false,
                userSearch: '',
                newConvEmpresaId: {{ $empresaId ?? 'null' }},
                newConvName: '',
                selectedMembers: [],

                empresaId: {{ $empresaId ?? 'null' }},
                empresaUsers: @json($empresaUsers),
                empresasLista: @json($empresasLista),
                currentUserId: {{ auth()->id() }},
                csrfToken: '{{ csrf_token() }}',

                init() {
                    @if ($firstConversation)
                        this.selectedConversationId = {{ $firstConversation->id }};
                        if (!this.selectedConversationLabel) {
                            this.selectedConversationLabel = 'Conversación #{{ $firstConversation->id }}';
                        }
                        this.selectConversation(this.selectedConversationId);
                    @endif
                },

                get filteredUsers() {
                    const term = this.userSearch.toLowerCase();
                    if (!term) {
                        return this.empresaUsers;
                    }
                    return this.empresaUsers.filter(u => {
                        const full = `${u.nombre ?? ''} ${u.apellido_paterno ?? ''} ${u.apellido_materno ?? ''} ${u.empresa_nombre ?? ''}`.toLowerCase();
                        return full.includes(term);
                    });
                },

                isMemberSelected(id) {
                    return this.selectedMembers.includes(id);
                },

                toggleMember(id) {
                    if (this.isMemberSelected(id)) {
                        this.selectedMembers = this.selectedMembers.filter(x => x !== id);
                    } else {
                        this.selectedMembers.push(id);
                    }
                },

                async selectConversation(id) {
                    this.selectedConversationId = id;
                    this.loadingMessages = true;
                    this.error = null;
                    this.messages = [];

                    try {
                        const res = await fetch(`/chat/conversations/${id}/messages`, {
                            headers: {
                                'Accept': 'application/json'
                            }
                        });

                        const data = await res.json();

                        if (!data.ok) {
                            this.error = data.message || 'Error al obtener mensajes.';
                            return;
                        }

                        this.messages = data.messages || [];
                    } catch (e) {
                        console.error(e);
                        this.error = 'Error al obtener mensajes.';
                    } finally {
                        this.loadingMessages = false;
                    }
                },

                async sendMessage() {
                    if (!this.messageText.trim() || !this.selectedConversationId) return;

                    this.sending = true;
                    this.error = null;

                    try {
                        const form = new FormData();
                        form.append('message', this.messageText.trim());
                        form.append('_token', this.csrfToken);

                        const res = await fetch(`/chat/conversations/${this.selectedConversationId}/messages`, {
                            method: 'POST',
                            body: form
                        });

                        const data = await res.json();

                        if (!data.ok) {
                            this.error = data.message || 'No se pudo enviar el mensaje.';
                            return;
                        }

                        if (data.message) {
                            this.messages.push(data.message);
                        }

                        this.messageText = '';
                    } catch (e) {
                        console.error(e);
                        this.error = 'No se pudo enviar el mensaje.';
                    } finally {
                        this.sending = false;
                    }
                },

                openModal() {
                    this.modalOpen = true;
                    this.error = null;
                    this.userSearch = '';
                    this.selectedMembers = this.currentUserId ? [this.currentUserId] : [];

                    if (!this.newConvEmpresaId && this.empresaId) {
                        this.newConvEmpresaId = this.empresaId;
                    }
                },

                closeModal() {
                    this.modalOpen = false;
                },

                async createConversation() {
                    this.error = null;

                    if (this.selectedMembers.length === 0) {
                        this.error = 'Selecciona al menos un participante.';
                        return;
                    }

                    try {
                        const form = new FormData();
                        if (this.newConvName) {
                            form.append('name', this.newConvName);
                        }
                        if (this.newConvEmpresaId) {
                            form.append('empresa_id', this.newConvEmpresaId);
                        }
                        this.selectedMembers.forEach(id => form.append('members[]', id));
                        form.append('_token', this.csrfToken);

                        const res = await fetch('/chat/conversations', {
                            method: 'POST',
                            body: form
                        });

                        // En éxito Laravel redirige al index -> recargamos
                        if (res.redirected) {
                            window.location = res.url;
                            return;
                        }

                        const data = await res.json().catch(() => null);
                        if (data && data.error) {
                            this.error = data.error || 'No se pudo crear la conversación.';
                            return;
                        }

                        window.location.reload();
                    } catch (e) {
                        console.error(e);
                        this.error = 'No se pudo crear la conversación.';
                    }
                }
            }
        }
    </script>
</x-app-layout>
