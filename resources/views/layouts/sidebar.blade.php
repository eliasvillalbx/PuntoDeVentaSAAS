{{-- resources/views/layouts/sidebar.blade.php --}}
@php
  $isActive = fn ($pattern) => request()->routeIs($pattern);
  $isVentasActive  = $isActive('ventas.index')  || $isActive('ventas.show')  || $isActive('ventas.edit')  || $isActive('ventas.create');
  $isComprasActive = $isActive('compras.index') || $isActive('compras.show') || $isActive('compras.edit') || $isActive('compras.create');
  $kpis = [
    'prefacturasPendientes' => $prefacturasPendientes ?? null,
  ];
@endphp

<aside
  x-data="{
    open: (JSON.parse(localStorage.getItem('sb_open') || '{}')) || {},
    init(){
      // Defaults seguros
      this.open.general      ??= true;
      this.open.personas     ??= true;
      this.open.catalogo     ??= true;
      this.open.compras      ??= true;   // ⬅️ NUEVO
      this.open.ventas       ??= true;
      this.open.facturacion  ??= true;
      this.open.admin        ??= true;
    },
    persist(){ localStorage.setItem('sb_open', JSON.stringify(this.open)); }
  }"
  x-init="$watch('open', () => persist())"
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
    <nav class="flex-1 overflow-y-auto p-2 space-y-5" role="menu">

      {{-- ===================== GENERAL ===================== --}}
      <section class="space-y-1">
        <button type="button"
                class="group w-full flex items-center gap-2 rounded-lg text-[11px] font-semibold tracking-wide
                       text-gray-600 hover:text-gray-800 hover:bg-gray-50 px-3 py-2"
                :class="!sidebarOpen ? 'justify-center' : ''"
                @click="open.general = !open.general"
                :aria-expanded="String(!!open.general)"
                aria-controls="sec-general"
                title="General">
          <span class="material-symbols-outlined mi text-base">dashboard</span>
          <span class="uppercase" x-show="sidebarOpen" x-transition>GENERAL</span>
          <span class="ml-auto material-symbols-outlined mi text-sm transition-transform"
                x-show="sidebarOpen"
                :class="open.general ? 'rotate-0' : '-rotate-90'">expand_more</span>
          <span x-show="!sidebarOpen"
                class="pointer-events-none absolute left-14 z-50 whitespace-nowrap rounded-md border border-gray-200 bg-white px-2 py-1 text-xs text-gray-700 shadow-md opacity-0 group-hover:opacity-100">
            General
          </span>
        </button>

        <div id="sec-general" x-show="open.general" x-collapse class="space-y-1" x-cloak>
          <a href="{{ route('dashboard') }}"
             class="group relative flex items-center gap-3 px-3 py-2 rounded-lg {{ $isActive('dashboard') ? 'bg-gray-100 text-gray-900 ring-1 ring-gray-200' : 'text-gray-700 hover:bg-gray-50' }}"
             role="menuitem" aria-current="{{ $isActive('dashboard') ? 'page' : 'false' }}" title="Dashboard">
            @if($isActive('dashboard'))<span class="absolute left-0 top-1 bottom-1 w-1 bg-indigo-600 rounded-r"></span>@endif
            <span class="material-symbols-outlined mi">home</span>
            <span class="text-sm font-medium" x-show="sidebarOpen" x-transition>Dashboard</span>
            <span x-show="!sidebarOpen" x-transition class="pointer-events-none absolute left-14 z-50 rounded-md border border-gray-200 bg-white px-2 py-1 text-xs text-gray-700 shadow-md opacity-0 group-hover:opacity-100">Dashboard</span>
          </a>

          <a href="{{ route('empresas.index') }}"
             class="group relative flex items-center gap-3 px-3 py-2 rounded-lg {{ $isActive('empresas.*') ? 'bg-gray-100 text-gray-900 ring-1 ring-gray-200' : 'text-gray-700 hover:bg-gray-50' }}"
             role="menuitem" aria-current="{{ $isActive('empresas.*') ? 'page' : 'false' }}" title="Empresas">
            @if($isActive('empresas.*'))<span class="absolute left-0 top-1 bottom-1 w-1 bg-indigo-600 rounded-r"></span>@endif
            <span class="material-symbols-outlined mi">domain</span>
            <span class="text-sm font-medium" x-show="sidebarOpen" x-transition>Empresas</span>
            <span x-show="!sidebarOpen" x-transition class="pointer-events-none absolute left-14 z-50 rounded-md border border-gray-200 bg-white px-2 py-1 text-xs text-gray-700 shadow-md opacity-0 group-hover:opacity-100">Empresas</span>
          </a>
        </div>
      </section>

      {{-- ===================== PERSONAS ===================== --}}
      @if(auth()->user()?->hasAnyRole(['superadmin','administrador_empresa','gerente']))
      <section class="space-y-1">
        <button type="button"
                class="group w-full flex items-center gap-2 rounded-lg text-[11px] font-semibold tracking-wide
                       text-gray-600 hover:text-gray-800 hover:bg-gray-50 px-3 py-2"
                :class="!sidebarOpen ? 'justify-center' : ''"
                @click="open.personas = !open.personas"
                :aria-expanded="String(!!open.personas)"
                aria-controls="sec-personas"
                title="Personas">
          <span class="material-symbols-outlined mi text-base">group</span>
          <span class="uppercase" x-show="sidebarOpen" x-transition>PERSONAS</span>
          <span class="ml-auto material-symbols-outlined mi text-sm transition-transform"
                x-show="sidebarOpen"
                :class="open.personas ? 'rotate-0' : '-rotate-90'">expand_more</span>
          <span x-show="!sidebarOpen"
                class="pointer-events-none absolute left-14 z-50 whitespace-nowrap rounded-md border border-gray-200 bg-white px-2 py-1 text-xs text-gray-700 shadow-md opacity-0 group-hover:opacity-100">
            Personas
          </span>
        </button>

        <div id="sec-personas" x-show="open.personas" x-collapse class="space-y-1" x-cloak>
          <a href="{{ route('gerentes.index') }}"
             class="group relative flex items-center gap-3 px-3 py-2 rounded-lg {{ $isActive('gerentes.*') ? 'bg-gray-100 text-gray-900 ring-1 ring-gray-200' : 'text-gray-700 hover:bg-gray-50' }}"
             title="Gerentes">
            @if($isActive('gerentes.*'))<span class="absolute left-0 top-1 bottom-1 w-1 bg-indigo-600 rounded-r"></span>@endif
            <span class="material-symbols-outlined mi">manage_accounts</span>
            <span class="text-sm font-medium" x-show="sidebarOpen" x-transition>Gerentes</span>
            <span x-show="!sidebarOpen" class="pointer-events-none absolute left-14 z-50 rounded-md border border-gray-200 bg-white px-2 py-1 text-xs text-gray-700 shadow-md opacity-0 group-hover:opacity-100">Gerentes</span>
          </a>

          <a href="{{ route('vendedores.index') }}"
             class="group relative flex items-center gap-3 px-3 py-2 rounded-lg {{ $isActive('vendedores.*') ? 'bg-gray-100 text-gray-900 ring-1 ring-gray-200' : 'text-gray-700 hover:bg-gray-50' }}"
             title="Vendedores">
            @if($isActive('vendedores.*'))<span class="absolute left-0 top-1 bottom-1 w-1 bg-indigo-600 rounded-r"></span>@endif
            <span class="material-symbols-outlined mi">storefront</span>
            <span class="text-sm font-medium" x-show="sidebarOpen" x-transition>Vendedores</span>
            <span x-show="!sidebarOpen" class="pointer-events-none absolute left-14 z-50 rounded-md border border-gray-200 bg-white px-2 py-1 text-xs text-gray-700 shadow-md opacity-0 group-hover:opacity-100">Vendedores</span>
          </a>

          <a href="{{ route('clientes.index') }}"
             class="group relative flex items-center gap-3 px-3 py-2 rounded-lg {{ $isActive('clientes.*') ? 'bg-gray-100 text-gray-900 ring-1 ring-gray-200' : 'text-gray-700 hover:bg-gray-50' }}"
             title="Clientes">
            @if($isActive('clientes.*'))<span class="absolute left-0 top-1 bottom-1 w-1 bg-indigo-600 rounded-r"></span>@endif
            <span class="material-symbols-outlined mi">contacts</span>
            <span class="text-sm font-medium" x-show="sidebarOpen" x-transition>Clientes</span>
            <span x-show="!sidebarOpen" class="pointer-events-none absolute left-14 z-50 rounded-md border border-gray-200 bg-white px-2 py-1 text-xs text-gray-700 shadow-md opacity-0 group-hover:opacity-100">Clientes</span>
          </a>
        </div>
      </section>
      @endif

      {{-- ===================== CATÁLOGO ===================== --}}
      @if(auth()->user()?->hasAnyRole(['superadmin','administrador_empresa','gerente']))
      <section class="space-y-1">
        <button type="button"
                class="group w-full flex items-center gap-2 rounded-lg text-[11px] font-semibold tracking-wide
                       text-gray-600 hover:text-gray-800 hover:bg-gray-50 px-3 py-2"
                :class="!sidebarOpen ? 'justify-center' : ''"
                @click="open.catalogo = !open.catalogo"
                :aria-expanded="String(!!open.catalogo)"
                aria-controls="sec-catalogo"
                title="Catálogo">
          <span class="material-symbols-outlined mi text-base">inventory</span>
          <span class="uppercase" x-show="sidebarOpen" x-transition>CATÁLOGO</span>
          <span class="ml-auto material-symbols-outlined mi text-sm transition-transform"
                x-show="sidebarOpen"
                :class="open.catalogo ? 'rotate-0' : '-rotate-90'">expand_more</span>
          <span x-show="!sidebarOpen"
                class="pointer-events-none absolute left-14 z-50 whitespace-nowrap rounded-md border border-gray-200 bg-white px-2 py-1 text-xs text-gray-700 shadow-md opacity-0 group-hover:opacity-100">
            Catálogo
          </span>
        </button>

        <div id="sec-catalogo" x-show="open.catalogo" x-collapse class="space-y-1" x-cloak>
          <a href="{{ route('productos.index') }}"
             class="group relative flex items-center gap-3 px-3 py-2 rounded-lg {{ $isActive('productos.*') ? 'bg-gray-100 text-gray-900 ring-1 ring-gray-200' : 'text-gray-700 hover:bg-gray-50' }}"
             title="Productos">
            @if($isActive('productos.*'))<span class="absolute left-0 top-1 bottom-1 w-1 bg-indigo-600 rounded-r"></span>@endif
            <span class="material-symbols-outlined mi">inventory_2</span>
            <span class="text-sm font-medium" x-show="sidebarOpen" x-transition>Productos</span>
            <span x-show="!sidebarOpen" class="pointer-events-none absolute left-14 z-50 rounded-md border border-gray-200 bg-white px-2 py-1 text-xs text-gray-700 shadow-md opacity-0 group-hover:opacity-100">Productos</span>
          </a>

          <a href="{{ route('categorias.index') }}"
             class="group relative flex items-center gap-3 px-3 py-2 rounded-lg {{ $isActive('categorias.*') ? 'bg-gray-100 text-gray-900 ring-1 ring-gray-200' : 'text-gray-700 hover:bg-gray-50' }}"
             title="Categorías">
            @if($isActive('categorias.*'))<span class="absolute left-0 top-1 bottom-1 w-1 bg-indigo-600 rounded-r"></span>@endif
            <span class="material-symbols-outlined mi">category</span>
            <span class="text-sm font-medium" x-show="sidebarOpen" x-transition>Categorías</span>
            <span x-show="!sidebarOpen" class="pointer-events-none absolute left-14 z-50 rounded-md border border-gray-200 bg-white px-2 py-1 text-xs text-gray-700 shadow-md opacity-0 group-hover:opacity-100">Categorías</span>
          </a>

          <a href="{{ route('proveedores.index') }}"
             class="group relative flex items-center gap-3 px-3 py-2 rounded-lg {{ $isActive('proveedores.*') ? 'bg-gray-100 text-gray-900 ring-1 ring-gray-200' : 'text-gray-700 hover:bg-gray-50' }}"
             title="Proveedores">
            @if($isActive('proveedores.*'))<span class="absolute left-0 top-1 bottom-1 w-1 bg-indigo-600 rounded-r"></span>@endif
            <span class="material-symbols-outlined mi">local_shipping</span>
            <span class="text-sm font-medium" x-show="sidebarOpen" x-transition>Proveedores</span>
            <span x-show="!sidebarOpen" class="pointer-events-none absolute left-14 z-50 rounded-md border border-gray-200 bg-white px-2 py-1 text-xs text-gray-700 shadow-md opacity-0 group-hover:opacity-100">Proveedores</span>
          </a>
        </div>
      </section>
      @endif

      {{-- ===================== COMPRAS ===================== --}}
      @if(auth()->user()?->hasAnyRole(['superadmin','administrador_empresa','gerente']))
      <section class="space-y-1">
        <button type="button"
                class="group w-full flex items-center gap-2 rounded-lg text-[11px] font-semibold tracking-wide
                       text-gray-600 hover:text-gray-800 hover:bg-gray-50 px-3 py-2"
                :class="!sidebarOpen ? 'justify-center' : ''"
                @click="open.compras = !open.compras"
                :aria-expanded="String(!!open.compras)"
                aria-controls="sec-compras"
                title="Compras">
          <span class="material-symbols-outlined mi text-base">local_mall</span>
          <span class="uppercase" x-show="sidebarOpen" x-transition>COMPRAS</span>
          <span class="ml-auto material-symbols-outlined mi text-sm transition-transform"
                x-show="sidebarOpen"
                :class="open.compras ? 'rotate-0' : '-rotate-90'">expand_more</span>
          <span x-show="!sidebarOpen"
                class="pointer-events-none absolute left-14 z-50 whitespace-nowrap rounded-md border border-gray-200 bg-white px-2 py-1 text-xs text-gray-700 shadow-md opacity-0 group-hover:opacity-100">
            Compras
          </span>
        </button>

        <div id="sec-compras" x-show="open.compras" x-collapse class="space-y-1" x-cloak>
          <a href="{{ route('compras.index') }}"
             class="group relative flex items-center gap-3 px-3 py-2 rounded-lg {{ $isComprasActive ? 'bg-gray-100 text-gray-900 ring-1 ring-gray-200' : 'text-gray-700 hover:bg-gray-50' }}"
             title="Compras" aria-current="{{ $isComprasActive ? 'page' : 'false' }}">
            @if($isComprasActive)<span class="absolute left-0 top-1 bottom-1 w-1 bg-indigo-600 rounded-r"></span>@endif
            <span class="material-symbols-outlined mi">inventory</span>
            <span class="text-sm font-medium" x-show="sidebarOpen" x-transition>Compras</span>
            <span x-show="!sidebarOpen" class="pointer-events-none absolute left-14 z-50 rounded-md border border-gray-200 bg-white px-2 py-1 text-xs text-gray-700 shadow-md opacity-0 group-hover:opacity-100">Compras</span>
          </a>

          <a href="{{ route('compras.create') }}"
             class="group relative flex items-center gap-3 px-3 py-2 rounded-lg text-gray-700 hover:bg-gray-50"
             title="Nueva compra">
            <span class="material-symbols-outlined mi">add_shopping_cart</span>
            <span class="text-sm font-medium" x-show="sidebarOpen" x-transition>Nueva compra</span>
            <span x-show="!sidebarOpen" class="pointer-events-none absolute left-14 z-50 rounded-md border border-gray-200 bg-white px-2 py-1 text-xs text-gray-700 shadow-md opacity-0 group-hover:opacity-100">Nueva compra</span>
          </a>
        </div>
      </section>
      @endif

      {{-- ===================== VENTAS ===================== --}}
      @if(auth()->user()?->hasAnyRole(['superadmin','administrador_empresa','gerente','vendedor']))
      <section class="space-y-1">
        <button type="button"
                class="group w-full flex items-center gap-2 rounded-lg text-[11px] font-semibold tracking-wide
                       text-gray-600 hover:text-gray-800 hover:bg-gray-50 px-3 py-2"
                :class="!sidebarOpen ? 'justify-center' : ''"
                @click="open.ventas = !open.ventas"
                :aria-expanded="String(!!open.ventas)"
                aria-controls="sec-ventas"
                title="Ventas">
          <span class="material-symbols-outlined mi text-base">shopping_bag</span>
          <span class="uppercase" x-show="sidebarOpen" x-transition>VENTAS</span>
          <span class="ml-auto material-symbols-outlined mi text-sm transition-transform"
                x-show="sidebarOpen"
                :class="open.ventas ? 'rotate-0' : '-rotate-90'">expand_more</span>
          <span x-show="!sidebarOpen"
                class="pointer-events-none absolute left-14 z-50 whitespace-nowrap rounded-md border border-gray-200 bg-white px-2 py-1 text-xs text-gray-700 shadow-md opacity-0 group-hover:opacity-100">
            Ventas
          </span>
        </button>

        <div id="sec-ventas" x-show="open.ventas" x-collapse class="space-y-1" x-cloak>
          <a href="{{ route('ventas.index') }}"
             class="group relative flex items-center gap-3 px-3 py-2 rounded-lg {{ $isVentasActive ? 'bg-gray-100 text-gray-900 ring-1 ring-gray-200' : 'text-gray-700 hover:bg-gray-50' }}"
             title="Ventas" aria-current="{{ $isVentasActive ? 'page' : 'false' }}">
            @if($isVentasActive)<span class="absolute left-0 top-1 bottom-1 w-1 bg-indigo-600 rounded-r"></span>@endif
            <span class="material-symbols-outlined mi">point_of_sale</span>
            <span class="text-sm font-medium" x-show="sidebarOpen" x-transition>Ventas</span>
            <span x-show="!sidebarOpen" class="pointer-events-none absolute left-14 z-50 rounded-md border border-gray-200 bg-white px-2 py-1 text-xs text-gray-700 shadow-md opacity-0 group-hover:opacity-100">Ventas</span>
          </a>

          @php $isPref = request()->routeIs('ventas.index') && request('estatus') === 'prefactura'; @endphp
          <a href="{{ route('ventas.index', ['estatus' => 'prefactura']) }}"
             class="group relative flex items-center gap-3 px-3 py-2 rounded-lg {{ $isPref ? 'bg-gray-100 text-gray-900 ring-1 ring-gray-200' : 'text-gray-700 hover:bg-gray-50' }}"
             title="Prefacturas" aria-current="{{ $isPref ? 'page' : 'false' }}">
            @if($isPref)<span class="absolute left-0 top-1 bottom-1 w-1 bg-indigo-600 rounded-r"></span>@endif
            <span class="material-symbols-outlined mi">receipt_long</span>
            <span class="text-sm font-medium" x-show="sidebarOpen" x-transition>Prefacturas</span>
            @if(!empty($kpis['prefacturasPendientes']))
              <span class="ml-auto inline-flex items-center justify-center min-w-[1.25rem] h-5 px-1 rounded-full bg-amber-100 text-amber-700 text-[10px] font-semibold">
                {{ $kpis['prefacturasPendientes'] }}
              </span>
            @endif
            <span x-show="!sidebarOpen" class="pointer-events-none absolute left-14 z-50 rounded-md border border-gray-200 bg-white px-2 py-1 text-xs text-gray-700 shadow-md opacity-0 group-hover:opacity-100">Prefacturas</span>
          </a>

          <a href="{{ route('ventas.create') }}"
             class="group relative flex items-center gap-3 px-3 py-2 rounded-lg text-gray-700 hover:bg-gray-50"
             title="Nueva venta">
            <span class="material-symbols-outlined mi">add_shopping_cart</span>
            <span class="text-sm font-medium" x-show="sidebarOpen" x-transition>Nueva venta</span>
            <span x-show="!sidebarOpen" class="pointer-events-none absolute left-14 z-50 rounded-md border border-gray-200 bg-white px-2 py-1 text-xs text-gray-700 shadow-md opacity-0 group-hover:opacity-100">Nueva venta</span>
          </a>
        </div>
      </section>
      @endif

      {{-- ===================== FACTURACIÓN ===================== --}}
      <section class="space-y-1">
        <button type="button"
                class="group w-full flex items-center gap-2 rounded-lg text-[11px] font-semibold tracking-wide
                       text-gray-600 hover:text-gray-800 hover:bg-gray-50 px-3 py-2"
                :class="!sidebarOpen ? 'justify-center' : ''"
                @click="open.facturacion = !open.facturacion"
                :aria-expanded="String(!!open.facturacion)"
                aria-controls="sec-facturacion"
                title="Facturación">
          <span class="material-symbols-outlined mi text-base">payments</span>
          <span class="uppercase" x-show="sidebarOpen" x-transition>FACTURACIÓN</span>
          <span class="ml-auto material-symbols-outlined mi text-sm transition-transform"
                x-show="sidebarOpen"
                :class="open.facturacion ? 'rotate-0' : '-rotate-90'">expand_more</span>
          <span x-show="!sidebarOpen"
                class="pointer-events-none absolute left-14 z-50 whitespace-nowrap rounded-md border border-gray-200 bg-white px-2 py-1 text-xs text-gray-700 shadow-md opacity-0 group-hover:opacity-100">
            Facturación
          </span>
        </button>

        <div id="sec-facturacion" x-show="open.facturacion" x-collapse class="space-y-1" x-cloak>
          <a href="{{ route('suscripciones.index') }}"
             class="group relative flex items-center gap-3 px-3 py-2 rounded-lg {{ $isActive('suscripciones.*') ? 'bg-gray-100 text-gray-900 ring-1 ring-gray-200' : 'text-gray-700 hover:bg-gray-50' }}"
             title="Suscripciones" aria-current="{{ $isActive('suscripciones.*') ? 'page' : 'false' }}">
            @if($isActive('suscripciones.*'))<span class="absolute left-0 top-1 bottom-1 w-1 bg-indigo-600 rounded-r"></span>@endif
            <span class="material-symbols-outlined mi">subscriptions</span>
            <span class="text-sm font-medium" x-show="sidebarOpen" x-transition>Suscripciones</span>
            <span x-show="!sidebarOpen" class="pointer-events-none absolute left-14 z-50 rounded-md border border-gray-200 bg-white px-2 py-1 text-xs text-gray-700 shadow-md opacity-0 group-hover:opacity-100">Suscripciones</span>
          </a>
        </div>
      </section>

      {{-- ===================== ADMINISTRACIÓN (solo SA) ===================== --}}
      @role('superadmin')
      <section class="space-y-1">
        <button type="button"
                class="group w-full flex items-center gap-2 rounded-lg text-[11px] font-semibold tracking-wide
                       text-gray-600 hover:text-gray-800 hover:bg-gray-50 px-3 py-2"
                :class="!sidebarOpen ? 'justify-center' : ''"
                @click="open.admin = !open.admin"
                :aria-expanded="String(!!open.admin)"
                aria-controls="sec-admin"
                title="Administración">
          <span class="material-symbols-outlined mi text-base">admin_panel_settings</span>
          <span class="uppercase" x-show="sidebarOpen" x-transition>ADMINISTRACIÓN</span>
          <span class="ml-auto material-symbols-outlined mi text-sm transition-transform"
                x-show="sidebarOpen"
                :class="open.admin ? 'rotate-0' : '-rotate-90'">expand_more</span>
          <span x-show="!sidebarOpen"
                class="pointer-events-none absolute left-14 z-50 whitespace-nowrap rounded-md border border-gray-200 bg-white px-2 py-1 text-xs text-gray-700 shadow-md opacity-0 group-hover:opacity-100">
            Administración
          </span>
        </button>

        <div id="sec-admin" x-show="open.admin" x-collapse class="space-y-1" x-cloak>
          <a href="{{ route('admin-empresas.index') }}"
             class="group relative flex items-center gap-3 px-3 py-2 rounded-lg {{ $isActive('admin-empresas.*') ? 'bg-gray-100 text-gray-900 ring-1 ring-gray-200' : 'text-gray-700 hover:bg-gray-50' }}"
             title="Administradores de empresa" aria-current="{{ $isActive('admin-empresas.*') ? 'page' : 'false' }}">
            @if($isActive('admin-empresas.*'))<span class="absolute left-0 top-1 bottom-1 w-1 bg-indigo-600 rounded-r"></span>@endif
            <span class="material-symbols-outlined mi">workspace_premium</span>
            <span class="text-sm font-medium" x-show="sidebarOpen" x-transition>Administradores</span>
            <span x-show="!sidebarOpen" class="pointer-events-none absolute left-14 z-50 rounded-md border border-gray-200 bg-white px-2 py-1 text-xs text-gray-700 shadow-md opacity-0 group-hover:opacity-100">Administradores</span>
          </a>
        </div>
      </section>
      @endrole

    </nav>
  </div>
</aside>
