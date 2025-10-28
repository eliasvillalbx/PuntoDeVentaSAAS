{{-- resources/views/layouts/navbar.blade.php --}}
@php
  // Usuario actual
  $u = $u ?? auth()->user();

  // Avatar / iniciales
  $userAvatar = $userAvatar ?? data_get($u, 'avatar_url');
  $userInitials = $userInitials ?? function ($name) {
      $name = trim((string) $name);
      if ($name === '') return 'U';
      $parts = preg_split('/\s+/', $name);
      $a = mb_substr($parts[0] ?? 'U', 0, 1);
      $b = isset($parts[1]) ? mb_substr($parts[1], 0, 1) : '';
      return mb_strtoupper($a.$b);
  };
@endphp

<nav class="h-16 sticky top-0 z-50
            bg-white/70 backdrop-blur supports-backdrop-blur
            border-b border-gray-200/80
            shadow-[0_1px_15px_-6px_rgba(0,0,0,.15)]">
  <div class="h-[calc(4rem-2px)] px-4 sm:px-6 lg:px-8 flex items-center justify-between">
    {{-- Izquierda: botón menú --}}
    <div class="flex items-center">
      <button type="button"
              @click="typeof toggleSidebar === 'function' && toggleSidebar()"
              class="group inline-flex items-center justify-center h-9 w-9 rounded-xl border border-gray-200/80
                     bg-white/80 hover:bg-gray-50 transition
                     focus:outline-none focus:ring-2 focus:ring-indigo-500/50"
              :title="(typeof sidebarOpen !== 'undefined' && sidebarOpen) ? 'Colapsar menú' : 'Expandir menú'"
              aria-label="Alternar menú lateral">
        <span class="material-symbols-outlined mi transition-transform group-active:scale-95">menu</span>
      </button>
    </div>

    {{-- (Centro vacío) --}}

    {{-- Derecha: Perfil con dropdown (sin mensajes flash) --}}
    <div class="relative flex items-center gap-3"
         x-data="{ profileOpen:false }"
         @keydown.escape.window="profileOpen=false">

      {{-- Info del usuario --}}
      <div class="hidden sm:flex flex-col text-right leading-tight">
        <div class="text-sm text-gray-900 font-semibold">
          {{ $u?->name ?? 'Usuario' }}
        </div>
        <div class="text-xs text-gray-500 truncate max-w-[220px]">
          {{ $u?->email ?? '' }}
        </div>
      </div>

      {{-- Avatar con indicador de activo (corregido) --}}
      <button type="button"
              @click.stop="profileOpen = !profileOpen"
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
            {{ $userInitials($u?->name) }}
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
