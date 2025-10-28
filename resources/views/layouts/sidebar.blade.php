{{-- resources/views/layouts/sidebar.blade.php --}}
@php
  $isActive = fn ($pattern) => request()->routeIs($pattern) ? 'bg-gray-100 text-gray-900 ring-1 ring-gray-200' : 'text-gray-700 hover:bg-gray-50';
@endphp

<aside class="sticky top-16 h-[calc(100vh-4rem)] bg-white border-r border-gray-200 transition-all duration-200 ease-out"
       :class="sidebarOpen ? 'w-64' : 'w-16'"
       role="complementary" aria-label="Sidebar de navegación">

  <div class="h-full flex flex-col">
    {{-- Header --}}
    <div class="h-12 flex items-center px-3 border-b border-gray-200">
      <span class="text-xs font-semibold text-gray-500" x-show="sidebarOpen" x-transition>MENÚ</span>
      <span class="mx-auto text-xs text-gray-400" x-show="!sidebarOpen" x-transition>MN</span>
    </div>

    {{-- Items --}}
    <nav class="flex-1 overflow-y-auto p-2 space-y-1" role="menu">
      {{-- Dashboard --}}
      <a href="{{ route('dashboard') }}"
         class="group flex items-center gap-3 px-3 py-2 rounded-lg {{ $isActive('dashboard') }}"
         role="menuitem" aria-label="Dashboard">
        <span class="material-symbols-outlined mi">dashboard</span>
        <span class="text-sm font-medium" x-show="sidebarOpen" x-transition>Dashboard</span>
      </a>

      {{-- Empresas --}}
      <a href="{{ route('empresas.index') }}"
         class="group flex items-center gap-3 px-3 py-2 rounded-lg {{ $isActive('empresas.*') }}"
         role="menuitem" aria-label="Empresas">
        <span class="material-symbols-outlined mi">apartment</span>
        <span class="text-sm font-medium" x-show="sidebarOpen" x-transition>Empresas</span>
      </a>
    </nav>

    {{-- (Eliminado) Footer con botón de colapsar --}}
  </div>
</aside>
