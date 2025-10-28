{{-- resources/views/layouts/sidebar.blade.php --}}
@php
  $isActive = fn ($pattern) => request()->routeIs($pattern);
@endphp

<aside
  x-data="{
    // estado de cada categoría (acordeón)
    open:{
      general:true,
      operacion:true,     // ← agregado
      facturacion:true,
      accesos:true,
    }
  }"
  class="sticky top-16 h-[calc(100vh-4rem)] bg-white/95 border-r border-gray-200 transition-all duration-200 ease-out backdrop-blur"
  :class="sidebarOpen ? 'w-64' : 'w-16'"
  role="complementary"
  aria-label="Sidebar de navegación"
>
  <div class="h-full flex flex-col">

    {{-- Header --}}
    <div class="h-12 flex items-center px-3 border-b border-gray-200/80">
      <span class="text-[11px] font-semibold text-gray-500 tracking-wide" x-show="sidebarOpen" x-transition>MENÚ</span>
      <span class="mx-auto text-xs text-gray-400" x-show="!sidebarOpen" x-transition>MN</span>
    </div>

    {{-- Items --}}
    <nav class="flex-1 overflow-y-auto p-2 space-y-4" role="menu">

      {{-- ===== Sección: General ===== --}}
      <div class="space-y-1">
        {{-- Encabezado de sección (acordeón) --}}
        <button type="button"
                class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-[11px] font-semibold tracking-wide
                       text-gray-500 hover:text-gray-700 hover:bg-gray-50"
                x-show="sidebarOpen" x-transition
                @click="open.general = !open.general"
                :aria-expanded="open.general.toString()"
                aria-controls="sec-general">
          <span class="material-symbols-outlined mi text-base">dashboard</span>
          GENERAL
          <span class="ml-auto material-symbols-outlined mi text-sm transition-transform"
                :class="open.general ? 'rotate-0' : '-rotate-90'">expand_more</span>
        </button>

        {{-- Cuando está colapsado, un separador sutil --}}
        <div class="px-1" x-show="!sidebarOpen" x-transition>
          <div class="h-px bg-gradient-to-r from-transparent via-gray-200 to-transparent"></div>
        </div>

        <div id="sec-general" x-show="open.general" x-collapse class="space-y-1" x-cloak>
          {{-- Dashboard --}}
          <a
            href="{{ route('dashboard') }}"
            class="group relative flex items-center gap-3 px-3 py-2 rounded-lg
                   {{ $isActive('dashboard') ? 'bg-gray-100 text-gray-900 ring-1 ring-gray-200' : 'text-gray-700 hover:bg-gray-50' }}"
            role="menuitem"
            aria-label="Dashboard"
            aria-current="{{ $isActive('dashboard') ? 'page' : 'false' }}"
            title="Dashboard"
          >
            @if($isActive('dashboard'))
              <span class="absolute left-0 top-1 bottom-1 w-1 bg-indigo-600 rounded-r"></span>
            @endif
            <span class="material-symbols-outlined mi">home</span>
            <span class="text-sm font-medium" x-show="sidebarOpen" x-transition>Dashboard</span>

            {{-- Tooltip colapsado --}}
            <span
              x-show="!sidebarOpen"
              x-transition
              class="pointer-events-none absolute left-14 z-50 whitespace-nowrap rounded-md border border-gray-200 bg-white px-2 py-1 text-xs text-gray-700 shadow-md opacity-0 group-hover:opacity-100"
            >
              Dashboard
            </span>
          </a>

          {{-- Empresas --}}
          <a
            href="{{ route('empresas.index') }}"
            class="group relative flex items-center gap-3 px-3 py-2 rounded-lg
                   {{ $isActive('empresas.*') ? 'bg-gray-100 text-gray-900 ring-1 ring-gray-200' : 'text-gray-700 hover:bg-gray-50' }}"
            role="menuitem"
            aria-label="Empresas"
            aria-current="{{ $isActive('empresas.*') ? 'page' : 'false' }}"
            title="Empresas"
          >
            @if($isActive('empresas.*'))
              <span class="absolute left-0 top-1 bottom-1 w-1 bg-indigo-600 rounded-r"></span>
            @endif
            <span class="material-symbols-outlined mi">apartment</span>
            <span class="text-sm font-medium" x-show="sidebarOpen" x-transition>Empresas</span>

            {{-- Tooltip colapsado --}}
            <span
              x-show="!sidebarOpen"
              x-transition
              class="pointer-events-none absolute left-14 z-50 whitespace-nowrap rounded-md border border-gray-200 bg-white px-2 py-1 text-xs text-gray-700 shadow-md opacity-0 group-hover:opacity-100"
            >
              Empresas
            </span>
          </a>
        </div>
      </div>

      {{-- ===== Sección: Operación (Gerentes / Vendedores / Catálogo) ===== --}}
      @if(auth()->user()?->hasAnyRole(['superadmin','administrador_empresa','gerente']))
      <div class="space-y-1">
        {{-- Encabezado (acordeón) --}}
        <button type="button"
                class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-[11px] font-semibold tracking-wide
                       text-gray-500 hover:text-gray-700 hover:bg-gray-50"
                x-show="sidebarOpen" x-transition
                @click="open.operacion = !open.operacion"
                :aria-expanded="open.operacion.toString()"
                aria-controls="sec-operacion">
          <span class="material-symbols-outlined mi text-base">work</span>
          OPERACIÓN
          <span class="ml-auto material-symbols-outlined mi text-sm transition-transform"
                :class="open.operacion ? 'rotate-0' : '-rotate-90'">expand_more</span>
        </button>

        <div class="px-1" x-show="!sidebarOpen" x-transition>
          <div class="h-px bg-gradient-to-r from-transparent via-gray-200 to-transparent"></div>
        </div>

        <div id="sec-operacion" x-show="open.operacion" x-collapse class="space-y-1" x-cloak>
          {{-- Gerentes --}}
          <a
            href="{{ route('gerentes.index') }}"
            class="group relative flex items-center gap-3 px-3 py-2 rounded-lg
                   {{ $isActive('gerentes.*') ? 'bg-gray-100 text-gray-900 ring-1 ring-gray-200' : 'text-gray-700 hover:bg-gray-50' }}"
            role="menuitem"
            aria-label="Gerentes"
            aria-current="{{ $isActive('gerentes.*') ? 'page' : 'false' }}"
            title="Gerentes"
          >
            @if($isActive('gerentes.*'))
              <span class="absolute left-0 top-1 bottom-1 w-1 bg-indigo-600 rounded-r"></span>
            @endif
            <span class="material-symbols-outlined mi">supervisor_account</span>
            <span class="text-sm font-medium" x-show="sidebarOpen" x-transition>Gerentes</span>

            {{-- Tooltip colapsado --}}
            <span
              x-show="!sidebarOpen"
              x-transition
              class="pointer-events-none absolute left-14 z-50 whitespace-nowrap rounded-md border border-gray-200 bg-white px-2 py-1 text-xs text-gray-700 shadow-md opacity-0 group-hover:opacity-100"
            >
              Gerentes
            </span>
          </a>

          {{-- Vendedores --}}
          <a
            href="{{ route('vendedores.index') }}"
            class="group relative flex items-center gap-3 px-3 py-2 rounded-lg
                   {{ $isActive('vendedores.*') ? 'bg-gray-100 text-gray-900 ring-1 ring-gray-200' : 'text-gray-700 hover:bg-gray-50' }}"
            role="menuitem"
            aria-label="Vendedores"
            aria-current="{{ $isActive('vendedores.*') ? 'page' : 'false' }}"
            title="Vendedores"
          >
            @if($isActive('vendedores.*'))
              <span class="absolute left-0 top-1 bottom-1 w-1 bg-indigo-600 rounded-r"></span>
            @endif
            <span class="material-symbols-outlined mi">groups</span>
            <span class="text-sm font-medium" x-show="sidebarOpen" x-transition>Vendedores</span>

            {{-- Tooltip colapsado --}}
            <span
              x-show="!sidebarOpen"
              x-transition
              class="pointer-events-none absolute left-14 z-50 whitespace-nowrap rounded-md border border-gray-200 bg-white px-2 py-1 text-xs text-gray-700 shadow-md opacity-0 group-hover:opacity-100"
            >
              Vendedores
            </span>
          </a>

          {{-- --- Divider visual --- --}}
          <div class="px-3 pt-2">
            <div class="h-px bg-gray-100"></div>
          </div>

          {{-- Categorías --}}
          <a
            href="{{ route('categorias.index') }}"
            class="group relative flex items-center gap-3 px-3 py-2 rounded-lg
                   {{ $isActive('categorias.*') ? 'bg-gray-100 text-gray-900 ring-1 ring-gray-200' : 'text-gray-700 hover:bg-gray-50' }}"
            role="menuitem"
            aria-label="Categorías"
            aria-current="{{ $isActive('categorias.*') ? 'page' : 'false' }}"
            title="Categorías"
          >
            @if($isActive('categorias.*'))
              <span class="absolute left-0 top-1 bottom-1 w-1 bg-indigo-600 rounded-r"></span>
            @endif
            <span class="material-symbols-outlined mi">label</span>
            <span class="text-sm font-medium" x-show="sidebarOpen" x-transition>Categorías</span>

            <span
              x-show="!sidebarOpen"
              x-transition
              class="pointer-events-none absolute left-14 z-50 whitespace-nowrap rounded-md border border-gray-200 bg-white px-2 py-1 text-xs text-gray-700 shadow-md opacity-0 group-hover:opacity-100"
            >
              Categorías
            </span>
          </a>

          {{-- Proveedores --}}
          <a
            href="{{ route('proveedores.index') }}"
            class="group relative flex items-center gap-3 px-3 py-2 rounded-lg
                   {{ $isActive('proveedores.*') ? 'bg-gray-100 text-gray-900 ring-1 ring-gray-200' : 'text-gray-700 hover:bg-gray-50' }}"
            role="menuitem"
            aria-label="Proveedores"
            aria-current="{{ $isActive('proveedores.*') ? 'page' : 'false' }}"
            title="Proveedores"
          >
            @if($isActive('proveedores.*'))
              <span class="absolute left-0 top-1 bottom-1 w-1 bg-indigo-600 rounded-r"></span>
            @endif
            <span class="material-symbols-outlined mi">local_shipping</span>
            <span class="text-sm font-medium" x-show="sidebarOpen" x-transition>Proveedores</span>

            <span
              x-show="!sidebarOpen"
              x-transition
              class="pointer-events-none absolute left-14 z-50 whitespace-nowrap rounded-md border border-gray-200 bg-white px-2 py-1 text-xs text-gray-700 shadow-md opacity-0 group-hover:opacity-100"
            >
              Proveedores
            </span>
          </a>

          {{-- Productos --}}
          <a
            href="{{ route('productos.index') }}"
            class="group relative flex items-center gap-3 px-3 py-2 rounded-lg
                   {{ $isActive('productos.*') ? 'bg-gray-100 text-gray-900 ring-1 ring-gray-200' : 'text-gray-700 hover:bg-gray-50' }}"
            role="menuitem"
            aria-label="Productos"
            aria-current="{{ $isActive('productos.*') ? 'page' : 'false' }}"
            title="Productos"
          >
            @if($isActive('productos.*'))
              <span class="absolute left-0 top-1 bottom-1 w-1 bg-indigo-600 rounded-r"></span>
            @endif
            <span class="material-symbols-outlined mi">inventory_2</span>
            <span class="text-sm font-medium" x-show="sidebarOpen" x-transition>Productos</span>

            <span
              x-show="!sidebarOpen"
              x-transition
              class="pointer-events-none absolute left-14 z-50 whitespace-nowrap rounded-md border border-gray-200 bg-white px-2 py-1 text-xs text-gray-700 shadow-md opacity-0 group-hover:opacity-100"
            >
              Productos
            </span>
          </a>
        </div>
      </div>
      @endif

      {{-- ===== Sección: Facturación ===== --}}
      <div class="space-y-1">
        {{-- Encabezado (acordeón) --}}
        <button type="button"
                class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-[11px] font-semibold tracking-wide
                       text-gray-500 hover:text-gray-700 hover:bg-gray-50"
                x-show="sidebarOpen" x-transition
                @click="open.facturacion = !open.facturacion"
                :aria-expanded="open.facturacion.toString()"
                aria-controls="sec-facturacion">
          <span class="material-symbols-outlined mi text-base">payments</span>
          FACTURACIÓN
          <span class="ml-auto material-symbols-outlined mi text-sm transition-transform"
                :class="open.facturacion ? 'rotate-0' : '-rotate-90'">expand_more</span>
        </button>

        <div class="px-1" x-show="!sidebarOpen" x-transition>
          <div class="h-px bg-gradient-to-r from-transparent via-gray-200 to-transparent"></div>
        </div>

        <div id="sec-facturacion" x-show="open.facturacion" x-collapse class="space-y-1" x-cloak>
          {{-- Suscripciones --}}
          <a
            href="{{ route('suscripciones.index') }}"
            class="group relative flex items-center gap-3 px-3 py-2 rounded-lg
                   {{ $isActive('suscripciones.*') ? 'bg-gray-100 text-gray-900 ring-1 ring-gray-200' : 'text-gray-700 hover:bg-gray-50' }}"
            role="menuitem"
            aria-label="Suscripciones"
            aria-current="{{ $isActive('suscripciones.*') ? 'page' : 'false' }}"
            title="Suscripciones"
          >
            @if($isActive('suscripciones.*'))
              <span class="absolute left-0 top-1 bottom-1 w-1 bg-indigo-600 rounded-r"></span>
            @endif
            <span class="material-symbols-outlined mi">subscriptions</span>
            <span class="text-sm font-medium" x-show="sidebarOpen" x-transition>Suscripciones</span>

            {{-- Tooltip colapsado --}}
            <span
              x-show="!sidebarOpen"
              x-transition
              class="pointer-events-none absolute left-14 z-50 whitespace-nowrap rounded-md border border-gray-200 bg-white px-2 py-1 text-xs text-gray-700 shadow-md opacity-0 group-hover:opacity-100"
            >
              Suscripciones
            </span>
          </a>
        </div>
      </div>

      {{-- ===== Sección: Accesos (solo superadmin) ===== --}}
      @role('superadmin')
      <div class="space-y-1">
        {{-- Encabezado (acordeón) --}}
        <button type="button"
                class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-[11px] font-semibold tracking-wide
                       text-gray-500 hover:text-gray-700 hover:bg-gray-50"
                x-show="sidebarOpen" x-transition
                @click="open.accesos = !open.accesos"
                :aria-expanded="open.accesos.toString()"
                aria-controls="sec-accesos">
          <span class="material-symbols-outlined mi text-base">admin_panel_settings</span>
          ACCESOS
          <span class="ml-auto material-symbols-outlined mi text-sm transition-transform"
                :class="open.accesos ? 'rotate-0' : '-rotate-90'">expand_more</span>
        </button>

        <div class="px-1" x-show="!sidebarOpen" x-transition>
          <div class="h-px bg-gradient-to-r from-transparent via-gray-200 to-transparent"></div>
        </div>

        <div id="sec-accesos" x-show="open.accesos" x-collapse class="space-y-1" x-cloak>
          {{-- Administradores de empresa --}}
          <a
            href="{{ route('admin-empresas.index') }}"
            class="group relative flex items-center gap-3 px-3 py-2 rounded-lg
                   {{ $isActive('admin-empresas.*') ? 'bg-gray-100 text-gray-900 ring-1 ring-gray-200' : 'text-gray-700 hover:bg-gray-50' }}"
            role="menuitem"
            aria-label="Administradores de empresa"
            aria-current="{{ $isActive('admin-empresas.*') ? 'page' : 'false' }}"
            title="Administradores de empresa"
          >
            @if($isActive('admin-empresas.*'))
              <span class="absolute left-0 top-1 bottom-1 w-1 bg-indigo-600 rounded-r"></span>
            @endif
            <span class="material-symbols-outlined mi">supervisor_account</span>
            <span class="text-sm font-medium" x-show="sidebarOpen" x-transition>Administradores</span>

            {{-- Tooltip colapsado --}}
            <span
              x-show="!sidebarOpen"
              x-transition
              class="pointer-events-none absolute left-14 z-50 whitespace-nowrap rounded-md border border-gray-200 bg-white px-2 py-1 text-xs text-gray-700 shadow-md opacity-0 group-hover:opacity-100"
            >
              Administradores
            </span>
          </a>
        </div>
      </div>
      @endrole

    </nav>
  </div>
</aside>
