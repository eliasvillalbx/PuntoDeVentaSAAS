{{-- resources/views/layouts/navbar.blade.php --}}
@php
  // Usuario actual
  /** @var \App\Models\User|null $u */
  $u = $u ?? auth()->user();

  // Flag superadmin (puede venir desde app.blade)
  $isSuperAdmin = $isSuperAdmin ?? ($u && method_exists($u, 'hasRole') ? $u->hasRole('superadmin') : false);

  // Empresa (pueden venir desde app.blade)
  $empresaNombre = $empresaNombre ?? (!$isSuperAdmin ? (string) data_get($u, 'empresa.display_name', '') : '');
  $empresaLogo   = $empresaLogo   ?? (!$isSuperAdmin ? data_get($u, 'empresa.logo_url') : null);

  // Avatar / iniciales usuario
  $userAvatar = $userAvatar ?? data_get($u, 'avatar_url');
  $userInitials = $userInitials ?? function ($name) {
      $name = trim((string) $name);
      if ($name === '') return 'U';
      $parts = preg_split('/\s+/', $name);
      $a = mb_substr($parts[0] ?? 'U', 0, 1);
      $b = isset($parts[1]) ? mb_substr($parts[1], 0, 1) : '';
      return mb_strtoupper($a.$b);
  };

  // Notificaciones (últimas 10, solo de eventos de calendario)
  $unreadNotifications = collect();
  if ($u) {
      $unreadNotifications = $u->unreadNotifications()
          ->where('type', \App\Notifications\CalendarEventAssigned::class)
          ->latest()
          ->take(10)
          ->get();
  }
@endphp

<nav class="h-16 sticky top-0 z-50
            bg-white/70 backdrop-blur supports-backdrop-blur
            border-b border-gray-200/80
            shadow-[0_1px_15px_-6px_rgba(0,0,0,.15)]">
  <div class="h-[calc(4rem-2px)] px-4 sm:px-6 lg:px-8 flex items-center justify-between">
    {{-- Izquierda: botón menú + logo empresa --}}
    <div class="flex items-center gap-3">
      <button type="button"
              @click="typeof toggleSidebar === 'function' && toggleSidebar()"
              class="group inline-flex items-center justify-center h-9 w-9 rounded-xl border border-gray-200/80
                     bg-white/80 hover:bg-gray-50 transition
                     focus:outline-none focus:ring-2 focus:ring-indigo-500/50"
              :title="(typeof sidebarOpen !== 'undefined' && sidebarOpen) ? 'Colapsar menú' : 'Expandir menú'"
              aria-label="Alternar menú lateral">
        <span class="material-symbols-outlined mi transition-transform group-active:scale-95">menu</span>
      </button>

      {{-- Logo de la empresa (si no es superadmin) --}}
      @if(!$isSuperAdmin && $empresaLogo)
        <div class="flex items-center gap-2">
          <div class="h-9 w-9 rounded-xl overflow-hidden ring-1 ring-gray-200 bg-white">
            <img src="{{ $empresaLogo }}" alt="Logo empresa" class="h-full w-full object-cover">
          </div>
          @if($empresaNombre)
            <span class="hidden sm:inline-block text-sm font-semibold text-gray-800">
              {{ $empresaNombre }}
            </span>
          @endif
        </div>
      @elseif(!$isSuperAdmin && $empresaNombre)
        <span class="hidden sm:inline-block text-sm font-semibold text-gray-800">
          {{ $empresaNombre }}
        </span>
      @endif
    </div>

    {{-- Centro vacío por ahora --}}

    {{-- Derecha: Notificaciones + Perfil --}}
    <div class="relative flex items-center gap-3"
         x-data="{ profileOpen:false, notifOpen:false }"
         @keydown.escape.window="profileOpen=false; notifOpen=false">

      {{-- NOTIFICACIONES --}}
      @if($u)
        <div class="relative">
          <button type="button"
                  @click.stop="notifOpen = !notifOpen; profileOpen = false"
                  class="relative inline-flex items-center justify-center h-9 w-9 rounded-xl border border-gray-200/80
                         bg-white/80 hover:bg-gray-50 transition
                         focus:outline-none focus:ring-2 focus:ring-indigo-500/50"
                  aria-label="Ver notificaciones">
            <span class="material-symbols-outlined mi text-base">notifications</span>

            @if($unreadNotifications->count() > 0)
              <span class="absolute -top-1 -right-1 inline-flex items-center justify-center
                           rounded-full bg-red-500 text-white text-[10px] font-bold px-1 min-w-[16px] h-4">
                {{ $unreadNotifications->count() }}
              </span>
            @endif
          </button>

          {{-- Dropdown notificaciones --}}
          <div x-cloak
               x-show="notifOpen"
               x-transition.origin.top.right
               @click.outside="notifOpen=false"
               class="absolute right-0 mt-2 w-80 rounded-2xl bg-white/95 backdrop-blur
                      shadow-xl ring-1 ring-gray-200 overflow-hidden z-50">
            <div class="px-4 py-3 border-b border-gray-100">
              <div class="text-sm font-semibold text-gray-900">
                Notificaciones
              </div>
              <div class="text-xs text-gray-500">
                Eventos de calendario asignados
              </div>
            </div>

            <div class="max-h-80 overflow-y-auto">
              @forelse($unreadNotifications as $notif)
                @php
                  $data = $notif->data ?? [];
                  $title = $data['title'] ?? 'Evento de calendario';
                  $empresaNotif = $data['empresa_nombre'] ?? null;
                  $action = $data['action'] ?? 'created';
                @endphp
                <div class="px-4 py-3 border-b border-gray-100 text-xs hover:bg-gray-50">
                  <div class="flex items-start gap-2">
                    <span class="material-symbols-outlined mi text-base text-indigo-500 mt-0.5">
                      event
                    </span>
                    <div class="space-y-0.5">
                      <div class="font-semibold text-gray-900 truncate">
                        {{ $title }}
                      </div>
                      <div class="text-[11px] text-gray-600">
                        {{ $action === 'updated' ? 'Evento actualizado' : 'Nuevo evento asignado' }}
                        @if($empresaNotif)
                          · {{ $empresaNotif }}
                        @endif
                      </div>
                      <div class="text-[11px] text-gray-400">
                        {{ $notif->created_at?->diffForHumans() }}
                      </div>
                    </div>
                  </div>
                </div>
              @empty
                <div class="px-4 py-3 text-xs text-gray-500">
                  No tienes notificaciones pendientes.
                </div>
              @endforelse
            </div>
          </div>
        </div>
      @endif

      {{-- Info del usuario: AHORA muestra el NOMBRE, no el rol --}}
      <div class="hidden sm:flex flex-col text-right leading-tight">
        <div class="text-sm text-gray-900 font-semibold">
          {{ $u?->nombre?? 'Usuario' }} {{$u?->apellido_paterno ?? ''}}
        </div>
        <div class="text-xs text-gray-500 truncate max-w-[220px]">
          {{ $u?->email ?? '' }}
        </div>
      </div>

      {{-- Avatar --}}
      <button type="button"
              @click.stop="profileOpen = !profileOpen; notifOpen = false"
              class="relative h-10 w-10 rounded-full overflow-hidden grid place-items-center
                     ring-1 ring-gray-200 bg-white shadow-sm
                     focus:outline-none focus:ring-2 focus:ring-indigo-500/60"
              aria-haspopup="menu"
              :aria-expanded="profileOpen.toString()"
              aria-label="Abrir menú de perfil">
        @if ($userAvatar)
          <img src="{{ $userAvatar }}" alt="Avatar" class="h-full w-full object-cover" />
        @else
          <span class="text-sm font-semibold text-gray-600 select-none">
            {{ $userInitials($u?->nombre) }} {{$userInitials($u?->apellido_paterno)}}
          </span>
        @endif
      </button>

      {{-- Dropdown Perfil --}}
      <div x-cloak
           x-show="profileOpen"
           x-transition.origin.top.right
           @click.outside="profileOpen=false"
           class="absolute right-0 top-12 w-60 rounded-2xl bg-white/95 backdrop-blur
                  shadow-xl ring-1 ring-gray-200 overflow-hidden">
        {{-- Header del dropdown --}}
        <div class="px-4 py-3 border-b border-gray-100">
          <div class="text-sm font-semibold text-gray-900 truncate">{{ $u?->name }}</div>
          <div class="text-xs text-gray-500 truncate">{{ $u?->email }}</div>
        </div>

        {{-- Acciones --}}
        <div class="py-1">
          <a href="{{ route('profile.edit') }}"
             class="flex items-center gap-2.5 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition">
            <span class="material-symbols-outlined mi text-base">account_circle</span>
            Editar perfil
          </a>

          <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit"
                    class="w-full flex items-center gap-2.5 px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition">
              <span class="material-symbols-outlined mi text-base">logout</span>
              Cerrar sesión
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>
</nav>
