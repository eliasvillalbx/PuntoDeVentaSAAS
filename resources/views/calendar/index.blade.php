<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <h2 class="font-semibold text-xl text-gray-900 leading-tight">
                {{ __('Calendario de eventos') }}
            </h2>
            <p class="text-sm text-gray-600">
                Gestiona eventos por empresa y usuario.
                @if($canManage)
                    Puedes crear, mover y editar eventos dentro de tu empresa.
                @else
                    Solo puedes visualizar los eventos de tu empresa.
                @endif
            </p>
        </div>
    </x-slot>

    <div class="py-6" x-data="calendarPage()" x-init="init()">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            {{-- Mensajes de error global --}}
            <div
                x-show="error"
                x-text="error"
                x-cloak
                class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-2 text-sm text-red-700"
            ></div>

            <div class="bg-white border border-gray-200 shadow-sm sm:rounded-lg p-4 space-y-4">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                    <div class="space-y-1">
                        <h3 class="text-sm font-semibold text-gray-900">
                            Calendario de la empresa
                            <span class="text-indigo-700 font-bold">{{ $empresaNombre }}</span>
                        </h3>
                        <p class="text-xs text-gray-500">
                            Haz clic sobre un evento para ver detalles
                            @if($canManage)
                                , o arrástralo para moverlo.
                            @else
                                .
                            @endif
                        </p>
                    </div>

                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
                        {{-- Selector de empresa para SUPERADMIN --}}
                        @if ($isSuperAdmin && $empresasLista->count())
                            <form method="GET" action="{{ route('calendar.index') }}" class="flex items-center gap-2">
                                <select
                                    name="empresa_id"
                                    class="rounded-md border-gray-300 bg-white text-xs text-gray-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
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
                                    class="rounded-md border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-100"
                                >
                                    Cambiar
                                </button>
                            </form>
                        @endif

                        {{-- Botón crear evento --}}
                        @if ($canManage)
                            <button
                                type="button"
                                @click="openModal()"
                                class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-3 py-2 text-xs font-semibold text-white shadow-sm hover:bg-indigo-500"
                            >
                                <svg class="h-4 w-4 mr-1" viewBox="0 0 24 24" fill="none">
                                    <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                                Nuevo evento
                            </button>
                        @endif
                    </div>
                </div>

                {{-- Calendario --}}
                <div id="calendar" class="mt-2 border border-gray-200 rounded-lg overflow-hidden"></div>
            </div>
        </div>

        {{-- Modal crear / editar / ver evento --}}
        <div
            x-show="modalOpen"
            x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
        >
            <div class="w-full sm:max-w-lg rounded-xl border border-gray-200 bg-white shadow-xl flex flex-col max-h-[95vh]">
                {{-- Header --}}
                <div class="flex items-center justify-between border-b border-gray-200 px-4 py-3 bg-gray-50">
                    <div>
                        {{-- Lógica del título dinámica --}}
                        <h3 class="text-sm font-semibold text-gray-900" 
                            x-text="editingEventId ? (canManage ? 'Editar evento' : 'Detalles del evento') : 'Nuevo evento'">
                        </h3>
                        <p class="text-xs text-gray-500">
                            @if($isSuperAdmin)
                                Gestión de eventos globales.
                            @else
                                {{ $empresaNombre }}
                            @endif
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

                {{-- Cuerpo --}}
                <div class="flex-1 overflow-y-auto px-4 py-3 space-y-3 text-sm text-gray-900">
                    {{-- Empresa (solo SA) --}}
                    @if ($isSuperAdmin)
                        <div class="space-y-1">
                            <label class="block text-xs font-medium text-gray-700">Empresa</label>
                            <select
                                x-model="form.empresa_id"
                                :disabled="!canManage"
                                class="block w-full rounded-md border-gray-300 bg-white text-xs text-gray-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-gray-100 disabled:text-gray-500"
                            >
                                @foreach ($empresasLista as $e)
                                    <option value="{{ $e->id }}">{{ $e->display_name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    {{-- Título --}}
                    <div class="space-y-1">
                        <label class="block text-xs font-medium text-gray-700">Título</label>
                        <input
                            type="text"
                            x-model="form.title"
                            :disabled="!canManage"
                            class="block w-full rounded-md border-gray-300 bg-white text-xs text-gray-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-gray-100 disabled:text-gray-500"
                            placeholder="Ej. Junta con cliente..."
                        />
                    </div>

                    {{-- Fechas --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="space-y-1">
                            <label class="block text-xs font-medium text-gray-700">Inicio</label>
                            <input
                                type="datetime-local"
                                x-model="form.start"
                                :disabled="!canManage"
                                class="block w-full rounded-md border-gray-300 bg-white text-xs text-gray-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-gray-100 disabled:text-gray-500"
                            />
                        </div>

                        <div class="space-y-1">
                            <label class="block text-xs font-medium text-gray-700">Fin</label>
                            <input
                                type="datetime-local"
                                x-model="form.end"
                                :disabled="!canManage"
                                class="block w-full rounded-md border-gray-300 bg-white text-xs text-gray-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-gray-100 disabled:text-gray-500"
                            />
                        </div>
                    </div>

                    {{-- Todo el día --}}
                    <div class="flex items-center gap-2">
                        <input
                            id="all-day"
                            type="checkbox"
                            x-model="form.all_day"
                            :disabled="!canManage"
                            class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 disabled:opacity-50"
                        />
                        <label for="all-day" class="text-xs text-gray-700">Evento de todo el día</label>
                    </div>

                    {{-- Usuarios asignados --}}
                    <div class="space-y-1">
                        <label class="block text-xs font-medium text-gray-700">Usuarios asignados</label>
                        <div class="border border-gray-200 rounded-md p-2 max-h-40 overflow-y-auto space-y-1 bg-white" :class="!canManage ? 'bg-gray-50' : ''">
                            @forelse ($usuariosEmpresa as $u)
                                <label class="flex items-center gap-2 text-xs text-gray-700">
                                    <input
                                        type="checkbox"
                                        value="{{ $u->id }}"
                                        x-model="form.assigned_user_ids"
                                        :disabled="!canManage"
                                        class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 disabled:opacity-50"
                                    />
                                    <span>{{ $u->nombre }} {{ $u->apellido_paterno }}</span>
                                </label>
                            @empty
                                <p class="text-[11px] text-gray-500">No hay usuarios registrados.</p>
                            @endforelse
                        </div>
                    </div>

                    {{-- Descripción --}}
                    <div class="space-y-1">
                        <label class="block text-xs font-medium text-gray-700">Descripción</label>
                        <textarea
                            x-model="form.description"
                            rows="3"
                            :disabled="!canManage"
                            class="block w-full rounded-md border-gray-300 bg-white text-xs text-gray-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-gray-100 disabled:text-gray-500"
                            placeholder="Detalles del evento..."
                        ></textarea>
                    </div>

                    {{-- Color --}}
                    <div class="space-y-1">
                        <label class="block text-xs font-medium text-gray-700">Color</label>
                        <input
                            type="color"
                            x-model="form.color"
                            :disabled="!canManage"
                            class="h-7 w-12 border border-gray-300 rounded-md bg-white p-0 disabled:opacity-50"
                        />
                    </div>
                </div>

                {{-- Footer --}}
                <div class="border-t border-gray-200 px-4 py-3 flex items-center justify-between gap-3 text-xs bg-gray-50">
                    <div
                        class="text-[11px] text-red-700"
                        x-show="formError"
                        x-text="formError"
                        x-cloak
                    ></div>
                    <div class="ml-auto flex items-center gap-2">
                        {{-- Botón Cancelar/Cerrar (Siempre visible) --}}
                        <button
                            type="button"
                            class="rounded-md border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-100"
                            @click="closeModal()"
                            x-text="canManage ? 'Cancelar' : 'Cerrar'"
                        >
                        </button>

                        {{-- Botón Guardar (Solo si puede gestionar) --}}
                        <button
                            type="button"
                            x-show="canManage"
                            class="rounded-md bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-500 disabled:opacity-40 disabled:cursor-not-allowed"
                            :disabled="saving"
                            @click="saveEvent()"
                        >
                            <span x-text="saving ? 'Guardando...' : 'Guardar'"></span>
                        </button>

                        {{-- Botón Eliminar (Solo si puede gestionar y está editando) --}}
                        <button
                            type="button"
                            x-show="editingEventId && canManage"
                            class="rounded-md bg-red-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-red-500 disabled:opacity-40 disabled:cursor-not-allowed"
                            :disabled="deleting"
                            @click="deleteEvent()"
                        >
                            <span x-text="deleting ? 'Eliminando...' : 'Eliminar'"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- FullCalendar --}}
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
    <script>
        function calendarPage() {
            return {
                calendar: null,
                error: null,
                modalOpen: false,
                formError: null,
                saving: false,
                deleting: false,
                editingEventId: null,

                form: {
                    empresa_id: {{ $empresaId ? (int)$empresaId : 'null' }},
                    title: '',
                    description: '',
                    start: '',
                    end: '',
                    all_day: false,
                    color: '#4f46e5',
                    assigned_user_ids: [],
                },

                empresaId: {{ $empresaId ? (int)$empresaId : 'null' }},
                canManage: {{ $canManage ? 'true' : 'false' }},
                csrfToken: '{{ csrf_token() }}',

                init() {
                    const calendarEl = document.getElementById('calendar');
                    const self = this;

                    this.calendar = new FullCalendar.Calendar(calendarEl, {
                        initialView: 'dayGridMonth',
                        locale: 'es',
                        height: 'auto',
                        // Solo permitimos seleccionar (drag) y editar (drop/resize) si puede gestionar
                        selectable: this.canManage,
                        editable: this.canManage, 
                        eventResizableFromStart: this.canManage,
                        headerToolbar: {
                            left: 'prev,next today',
                            center: 'title',
                            right: 'dayGridMonth,timeGridWeek,timeGridDay',
                        },
                        select(info) {
                            if (!self.canManage) return;

                            self.editingEventId = null;
                            self.formError = null;
                            self.resetForm();

                            self.form.all_day = info.allDay;
                            self.form.start = self.toLocalDatetimeInput(info.start);
                            self.form.end = info.end ? self.toLocalDatetimeInput(info.end) : '';

                            self.modalOpen = true;
                        },
                        eventClick(info) {
                            // AQUÍ ESTABA EL CAMBIO PRINCIPAL:
                            // Ya NO retornamos si !canManage. Permitimos abrir el modal.
                            
                            const ev = info.event;
                            self.editingEventId = ev.id;
                            self.formError = null;

                            // Llenamos el formulario (aunque sea solo lectura)
                            self.form.title = ev.title;
                            self.form.description = ev.extendedProps?.description || '';
                            self.form.all_day = ev.allDay;
                            self.form.color = ev.backgroundColor || '#4f46e5';
                            self.form.start = self.toLocalDatetimeInput(ev.start);
                            self.form.end = ev.end ? self.toLocalDatetimeInput(ev.end) : '';
                            self.form.empresa_id = ev.extendedProps?.empresa_id || self.empresaId;
                            self.form.assigned_user_ids = ev.extendedProps?.assigned_user_ids || [];

                            self.modalOpen = true;
                        },
                        eventDrop: async function(info) {
                            if (!self.canManage) { info.revert(); return; }
                            try { await self.updateEventDates(info.event); } 
                            catch (e) { console.error(e); info.revert(); }
                        },
                        eventResize: async function(info) {
                            if (!self.canManage) { info.revert(); return; }
                            try { await self.updateEventDates(info.event); } 
                            catch (e) { console.error(e); info.revert(); }
                        },
                        events: async function(fetchInfo, successCallback, failureCallback) {
                            try {
                                const params = new URLSearchParams();
                                if (self.empresaId) params.append('empresa_id', self.empresaId);
                                const res = await fetch(`/calendar/events?${params.toString()}`, {
                                    headers: { 'Accept': 'application/json' }
                                });
                                const data = await res.json();
                                if (!data.ok) {
                                    self.error = data.message || 'Error al cargar eventos.';
                                    failureCallback();
                                    return;
                                }
                                self.error = null;
                                successCallback(data.events || []);
                            } catch (e) {
                                self.error = 'Error de conexión.';
                                failureCallback(e);
                            }
                        },
                    });

                    this.calendar.render();
                },

                resetForm() {
                    this.form.title = '';
                    this.form.description = '';
                    this.form.start = '';
                    this.form.end = '';
                    this.form.all_day = false;
                    this.form.color = '#4f46e5';
                    this.form.assigned_user_ids = [];
                    if (!this.form.empresa_id && this.empresaId) {
                        this.form.empresa_id = this.empresaId;
                    }
                },

                toLocalDatetimeInput(dateObj) {
                    if (!dateObj) return '';
                    const pad = (n) => n.toString().padStart(2, '0');
                    const y = dateObj.getFullYear();
                    const m = pad(dateObj.getMonth() + 1);
                    const d = pad(dateObj.getDate());
                    const h = pad(dateObj.getHours());
                    const min = pad(dateObj.getMinutes());
                    return `${y}-${m}-${d}T${h}:${min}`;
                },

                async updateEventDates(event) {
                    const id = event.id;
                    const payload = {
                        start: event.start.toISOString(),
                        _token: this.csrfToken,
                    };
                    if (event.end) payload.end = event.end.toISOString();

                    const res = await fetch(`/calendar/events/${id}`, {
                        method: 'PUT',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': this.csrfToken,
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify(payload),
                    });
                    const data = await res.json();
                    if (!data.ok) throw new Error(data.message);
                },

                openModal() {
                    // Solo permitir abrir modal "Nuevo" si puede gestionar
                    if (!this.canManage) return; 
                    this.editingEventId = null;
                    this.formError = null;
                    this.resetForm();
                    this.modalOpen = true;
                },

                closeModal() {
                    this.modalOpen = false;
                },

                async saveEvent() {
                    if (!this.canManage) return;
                    this.saving = true;
                    this.formError = null;

                    try {
                        const payload = {
                            title: this.form.title,
                            description: this.form.description,
                            start: this.form.start,
                            end: this.form.end || null,
                            all_day: this.form.all_day ? 1 : 0,
                            color: this.form.color,
                            empresa_id: this.form.empresa_id || this.empresaId,
                            assigned_user_ids: this.form.assigned_user_ids,
                        };

                        if (!payload.title || !payload.start) {
                            this.formError = 'El título y la fecha de inicio son obligatorios.';
                            this.saving = false;
                            return;
                        }

                        let url = '/calendar/events';
                        let method = 'POST';

                        if (this.editingEventId) {
                            url = `/calendar/events/${this.editingEventId}`;
                            method = 'PUT';
                        }

                        const res = await fetch(url, {
                            method,
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': this.csrfToken,
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify(payload),
                        });

                        const data = await res.json();
                        if (!data.ok) {
                            this.formError = data.message || 'No se pudo guardar.';
                            return;
                        }

                        this.calendar.refetchEvents();
                        this.modalOpen = false;
                    } catch (e) {
                        this.formError = 'Error al guardar.';
                    } finally {
                        this.saving = false;
                    }
                },

                async deleteEvent() {
                    if (!this.canManage || !this.editingEventId) return;
                    this.deleting = true;
                    try {
                        const res = await fetch(`/calendar/events/${this.editingEventId}`, {
                            method: 'DELETE',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': this.csrfToken,
                            },
                        });
                        const data = await res.json();
                        if (!data.ok) {
                            this.formError = data.message || 'No se pudo eliminar.';
                            return;
                        }
                        this.calendar.refetchEvents();
                        this.modalOpen = false;
                    } catch (e) {
                        this.formError = 'Error al eliminar.';
                    } finally {
                        this.deleting = false;
                    }
                },
            }
        }
    </script>
</x-app-layout>