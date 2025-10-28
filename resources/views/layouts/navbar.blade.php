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

  // NOTA: intencionalmente NO mostramos mensajes flash (success/error) ni $errors aquí.
@endphp

<nav class="h-16 bg-white/80 backdrop-blur border-b border-gray-200 sticky top-0 z-50">
  <div class="h-full px-4 sm:px-6 lg:px-8 flex items-center justify-between">

    {{-- Izquierda: botón para colapsar/expandir sidebar --}}
    <div class="flex items-center">
      <button type="button"
              @click="toggleSidebar && toggleSidebar()"
              class="inline-flex items-center justify-center h-9 w-9 rounded-xl border border-gray-200 bg-white/70 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition"
              :title="(typeof sidebarOpen !== 'undefined' && sidebarOpen) ? 'Colapsar menú' : 'Expandir menú'"
              aria-label="Alternar menú lateral">
        <span class="material-symbols-outlined mi">menu</span>
      </button>
    </div>

    {{-- Derecha: Perfil con dropdown (sin mensajes flash) --}}
    <div class="relative flex items-center gap-3" x-data="{ profileOpen: false }" @keydown.escape.window="profileOpen=false">
      <div class="hidden sm:flex flex-col text-right leading-tight">
        <div class="text-sm text-gray-900 font-semibold">
          {{ $u?->name ?? 'Usuario' }}
        </div>
        <div class="text-xs text-gray-500 truncate max-w-[220px]">
          {{ $u?->email ?? '' }}
        </div>
      </div>

      <button type="button"
              @click.stop="profileOpen = !profileOpen"
              class="h-10 w-10 rounded-full ring-1 ring-gray-200 overflow-hidden grid place-items-center bg-white shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
              aria-haspopup="menu"
              :aria-expanded="profileOpen">
        @if ($userAvatar)
          <img src="{{ $userAvatar }}" alt="Avatar" class="h-full w-full object-cover" />
        @else
          <span class="text-sm font-semibold text-gray-600">
            {{ $userInitials($u?->name) }}
          </span>
        @endif
      </button>

      {{-- Dropdown Perfil --}}
      <div x-cloak
           x-show="profileOpen"
           @click.outside="profileOpen = false"
           x-transition.origin.top.right
           class="absolute right-0 top-12 w-56 rounded-xl bg-white shadow-lg ring-1 ring-gray-200 overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-100">
          <div class="text-sm font-semibold text-gray-900">{{ $u?->name }}</div>
          <div class="text-xs text-gray-500 truncate">{{ $u?->email }}</div>
        </div>

        <div class="py-1">
          <a href="{{ route('profile.edit') }}"
             class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
            <span class="material-symbols-outlined mi text-base">account_circle</span>
            Editar perfil
          </a>

          <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit"
                    class="w-full flex items-center gap-2 px-4 py-2 text-sm text-red-600 hover:bg-red-50">
              <span class="material-symbols-outlined mi text-base">logout</span>
              Cerrar sesión
            </button>
          </form>
        </div>
      </div>
    </div>

  </div>
</nav>
