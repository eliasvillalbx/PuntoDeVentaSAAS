{{-- resources/views/layouts/sidebar.blade.php --}}
@php
  $isActive = fn ($pattern) => request()->routeIs($pattern);

  // Grupos activos para mantener el menú abierto
  $isVentasActive  = $isActive('ventas.index')  || $isActive('ventas.show')  || $isActive('ventas.edit')  || $isActive('ventas.create');
  $isComprasActive = $isActive('compras.index') || $isActive('compras.show') || $isActive('compras.edit') || $isActive('compras.create');
  
  // KPIs o Badges
  $kpis = [
    'prefacturasPendientes' => $prefacturasPendientes ?? null,
  ];
@endphp

<aside
  x-data="{
    open: (JSON.parse(localStorage.getItem('sb_open') || '{}')) || {},
    init(){
      // Defaults seguros (Secciones abiertas por defecto)
      this.open.general      ??= true;
      this.open.personas     ??= true;
      this.open.catalogo     ??= false;
      this.open.compras      ??= false;
      this.open.ventas       ??= true;
      this.open.membresia    ??= true; // Antes facturacion
      this.open.admin        ??= false; 
    },
    persist(){ localStorage.setItem('sb_open', JSON.stringify(this.open)); }
  }"
  x-init="$watch('open', () => persist())"
  class="sticky top-16 h-[calc(100vh-4rem)] bg-white/95 border-r border-gray-200 transition-all duration-200 ease-out backdrop-blur z-30"
  :class="sidebarOpen ? 'w-64' : 'w-16'"
  role="complementary"
  aria-label="Sidebar de navegación"
>
  <div class="h-full flex flex-col">

    {{-- Header --}}
    <div class="h-12 flex items-center px-3 border-b border-gray-200/80 bg-gray-50/50">
      <span class="text-[11px] font-bold text-gray-400 tracking-wider uppercase" x-show="sidebarOpen" x-transition>Navegación</span>
      <span class="mx-auto text-xs font-bold text-gray-400" x-show="!sidebarOpen" x-transition>NV</span>
    </div>

    {{-- Items --}}
    <nav class="flex-1 overflow-y-auto p-2 space-y-4 custom-scrollbar" role="menu">

      {{-- ===================== GENERAL ===================== --}}
      <section class="space-y-1">
        <button type="button"
                class="group w-full flex items-center gap-2 rounded-lg text-[11px] font-semibold tracking-wide
                       text-gray-500 hover:text-gray-800 hover:bg-gray-50 px-3 py-2 transition-colors"
                :class="!sidebarOpen ? 'justify-center' : ''"
                @click="open.general = !open.general">
          <span class="material-symbols-outlined mi text-base">grid_view</span>
          <span class="uppercase" x-show="sidebarOpen" x-transition>GENERAL</span>
          <span class="ml-auto material-symbols-outlined mi text-sm transition-transform duration-200"
                x-show="sidebarOpen"
                :class="open.general ? 'rotate-0' : '-rotate-90'">expand_more</span>
        </button>

        <div x-show="open.general" x-collapse class="space-y-1" x-cloak>
          {{-- Dashboard --}}
          <a href="{{ route('dashboard') }}"
             class="group relative flex items-center gap-3 px-3 py-2 rounded-lg {{ $isActive('dashboard') ? 'bg-indigo-50 text-indigo-700 font-medium' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}"
             title="Dashboard">
            @if($isActive('dashboard'))<span class="absolute left-0 top-1.5 bottom-1.5 w-1 bg-indigo-600 rounded-r-full"></span>@endif
            <span class="material-symbols-outlined mi {{ $isActive('dashboard') ? 'text-indigo-600' : 'text-gray-400 group-hover:text-gray-500' }}">home</span>
            <span class="text-sm" x-show="sidebarOpen" x-transition>Dashboard</span>
            <span x-show="!sidebarOpen" class="sr-only">Dashboard</span>
          </a>

          {{-- Calendario (NUEVO) --}}
          <a href="{{ route('calendar.index') }}"
             class="group relative flex items-center gap-3 px-3 py-2 rounded-lg {{ $isActive('calendar.*') ? 'bg-indigo-50 text-indigo-700 font-medium' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}"
             title="Calendario">
             @if($isActive('calendar.*'))<span class="absolute left-0 top-1.5 bottom-1.5 w-1 bg-indigo-600 rounded-r-full"></span>@endif
            <span class="material-symbols-outlined mi {{ $isActive('calendar.*') ? 'text-indigo-600' : 'text-gray-400 group-hover:text-gray-500' }}">calendar_month</span>
            <span class="text-sm" x-show="sidebarOpen" x-transition>Calendario</span>
          </a>

          {{-- Empresas --}}
          <a href="{{ route('empresas.index') }}"
             class="group relative flex items-center gap-3 px-3 py-2 rounded-lg {{ $isActive('empresas.*') ? 'bg-indigo-50 text-indigo-700 font-medium' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}"
             title="Empresas">
             @if($isActive('empresas.*'))<span class="absolute left-0 top-1.5 bottom-1.5 w-1 bg-indigo-600 rounded-r-full"></span>@endif
            <span class="material-symbols-outlined mi {{ $isActive('empresas.*') ? 'text-indigo-600' : 'text-gray-400 group-hover:text-gray-500' }}">domain</span>
            <span class="text-sm" x-show="sidebarOpen" x-transition>Empresas</span>
          </a>
        </div>
      </section>

      {{-- ===================== PERSONAS ===================== --}}
      @if(auth()->user()?->hasAnyRole(['superadmin','administrador_empresa','gerente']))
      <section class="space-y-1">
        <button type="button"
                class="group w-full flex items-center gap-2 rounded-lg text-[11px] font-semibold tracking-wide
                       text-gray-500 hover:text-gray-800 hover:bg-gray-50 px-3 py-2 transition-colors"
                :class="!sidebarOpen ? 'justify-center' : ''"
                @click="open.personas = !open.personas">
          <span class="material-symbols-outlined mi text-base">groups</span>
          <span class="uppercase" x-show="sidebarOpen" x-transition>PERSONAS</span>
          <span class="ml-auto material-symbols-outlined mi text-sm transition-transform duration-200"
                x-show="sidebarOpen"
                :class="open.personas ? 'rotate-0' : '-rotate-90'">expand_more</span>
        </button>

        <div x-show="open.personas" x-collapse class="space-y-1" x-cloak>
          
          {{-- Administradores (Movido aquí) --}}
          @role('superadmin')
          <a href="{{ route('admin-empresas.index') }}"
             class="group relative flex items-center gap-3 px-3 py-2 rounded-lg {{ $isActive('admin-empresas.*') ? 'bg-indigo-50 text-indigo-700 font-medium' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}"
             title="Administradores">
             @if($isActive('admin-empresas.*'))<span class="absolute left-0 top-1.5 bottom-1.5 w-1 bg-indigo-600 rounded-r-full"></span>@endif
            <span class="material-symbols-outlined mi {{ $isActive('admin-empresas.*') ? 'text-indigo-600' : 'text-gray-400 group-hover:text-gray-500' }}">verified_user</span>
            <span class="text-sm" x-show="sidebarOpen" x-transition>Administradores</span>
          </a>
          @endrole

          {{-- Gerentes --}}
          <a href="{{ route('gerentes.index') }}"
             class="group relative flex items-center gap-3 px-3 py-2 rounded-lg {{ $isActive('gerentes.*') ? 'bg-indigo-50 text-indigo-700 font-medium' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}"
             title="Gerentes">
            @if($isActive('gerentes.*'))<span class="absolute left-0 top-1.5 bottom-1.5 w-1 bg-indigo-600 rounded-r-full"></span>@endif
            <span class="material-symbols-outlined mi {{ $isActive('gerentes.*') ? 'text-indigo-600' : 'text-gray-400 group-hover:text-gray-500' }}">manage_accounts</span>
            <span class="text-sm" x-show="sidebarOpen" x-transition>Gerentes</span>
          </a>

          {{-- Vendedores --}}
          <a href="{{ route('vendedores.index') }}"
             class="group relative flex items-center gap-3 px-3 py-2 rounded-lg {{ $isActive('vendedores.*') ? 'bg-indigo-50 text-indigo-700 font-medium' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}"
             title="Vendedores">
            @if($isActive('vendedores.*'))<span class="absolute left-0 top-1.5 bottom-1.5 w-1 bg-indigo-600 rounded-r-full"></span>@endif
            <span class="material-symbols-outlined mi {{ $isActive('vendedores.*') ? 'text-indigo-600' : 'text-gray-400 group-hover:text-gray-500' }}">badge</span>
            <span class="text-sm" x-show="sidebarOpen" x-transition>Vendedores</span>
          </a>

          {{-- Clientes --}}
          <a href="{{ route('clientes.index') }}"
             class="group relative flex items-center gap-3 px-3 py-2 rounded-lg {{ $isActive('clientes.*') ? 'bg-indigo-50 text-indigo-700 font-medium' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}"
             title="Clientes">
            @if($isActive('clientes.*'))<span class="absolute left-0 top-1.5 bottom-1.5 w-1 bg-indigo-600 rounded-r-full"></span>@endif
            <span class="material-symbols-outlined mi {{ $isActive('clientes.*') ? 'text-indigo-600' : 'text-gray-400 group-hover:text-gray-500' }}">sentiment_satisfied</span>
            <span class="text-sm" x-show="sidebarOpen" x-transition>Clientes</span>
          </a>
        </div>
      </section>
      @endif

      {{-- ===================== CATÁLOGO ===================== --}}
      @if(auth()->user()?->hasAnyRole(['superadmin','administrador_empresa','gerente']))
      <section class="space-y-1">
        <button type="button"
                class="group w-full flex items-center gap-2 rounded-lg text-[11px] font-semibold tracking-wide
                       text-gray-500 hover:text-gray-800 hover:bg-gray-50 px-3 py-2 transition-colors"
                :class="!sidebarOpen ? 'justify-center' : ''"
                @click="open.catalogo = !open.catalogo">
          <span class="material-symbols-outlined mi text-base">inventory_2</span>
          <span class="uppercase" x-show="sidebarOpen" x-transition>CATÁLOGO</span>
          <span class="ml-auto material-symbols-outlined mi text-sm transition-transform duration-200"
                x-show="sidebarOpen"
                :class="open.catalogo ? 'rotate-0' : '-rotate-90'">expand_more</span>
        </button>

        <div x-show="open.catalogo" x-collapse class="space-y-1" x-cloak>
          <a href="{{ route('productos.index') }}"
             class="group relative flex items-center gap-3 px-3 py-2 rounded-lg {{ $isActive('productos.*') ? 'bg-indigo-50 text-indigo-700 font-medium' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
             @if($isActive('productos.*'))<span class="absolute left-0 top-1.5 bottom-1.5 w-1 bg-indigo-600 rounded-r-full"></span>@endif
            <span class="material-symbols-outlined mi {{ $isActive('productos.*') ? 'text-indigo-600' : 'text-gray-400 group-hover:text-gray-500' }}">package_2</span>
            <span class="text-sm" x-show="sidebarOpen" x-transition>Productos</span>
          </a>

          <a href="{{ route('categorias.index') }}"
             class="group relative flex items-center gap-3 px-3 py-2 rounded-lg {{ $isActive('categorias.*') ? 'bg-indigo-50 text-indigo-700 font-medium' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
             @if($isActive('categorias.*'))<span class="absolute left-0 top-1.5 bottom-1.5 w-1 bg-indigo-600 rounded-r-full"></span>@endif
            <span class="material-symbols-outlined mi {{ $isActive('categorias.*') ? 'text-indigo-600' : 'text-gray-400 group-hover:text-gray-500' }}">category</span>
            <span class="text-sm" x-show="sidebarOpen" x-transition>Categorías</span>
          </a>

          <a href="{{ route('proveedores.index') }}"
             class="group relative flex items-center gap-3 px-3 py-2 rounded-lg {{ $isActive('proveedores.*') ? 'bg-indigo-50 text-indigo-700 font-medium' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
             @if($isActive('proveedores.*'))<span class="absolute left-0 top-1.5 bottom-1.5 w-1 bg-indigo-600 rounded-r-full"></span>@endif
            <span class="material-symbols-outlined mi {{ $isActive('proveedores.*') ? 'text-indigo-600' : 'text-gray-400 group-hover:text-gray-500' }}">local_shipping</span>
            <span class="text-sm" x-show="sidebarOpen" x-transition>Proveedores</span>
          </a>
        </div>
      </section>
      @endif

      {{-- ===================== COMPRAS ===================== --}}
      @if(auth()->user()?->hasAnyRole(['superadmin','administrador_empresa','gerente']))
      <section class="space-y-1">
        <button type="button"
                class="group w-full flex items-center gap-2 rounded-lg text-[11px] font-semibold tracking-wide
                       text-gray-500 hover:text-gray-800 hover:bg-gray-50 px-3 py-2 transition-colors"
                :class="!sidebarOpen ? 'justify-center' : ''"
                @click="open.compras = !open.compras">
          <span class="material-symbols-outlined mi text-base">shopping_basket</span>
          <span class="uppercase" x-show="sidebarOpen" x-transition>COMPRAS</span>
          <span class="ml-auto material-symbols-outlined mi text-sm transition-transform duration-200"
                x-show="sidebarOpen"
                :class="open.compras ? 'rotate-0' : '-rotate-90'">expand_more</span>
        </button>

        <div x-show="open.compras" x-collapse class="space-y-1" x-cloak>
          <a href="{{ route('compras.index') }}"
             class="group relative flex items-center gap-3 px-3 py-2 rounded-lg {{ $isComprasActive ? 'bg-indigo-50 text-indigo-700 font-medium' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
             @if($isComprasActive)<span class="absolute left-0 top-1.5 bottom-1.5 w-1 bg-indigo-600 rounded-r-full"></span>@endif
            <span class="material-symbols-outlined mi {{ $isComprasActive ? 'text-indigo-600' : 'text-gray-400 group-hover:text-gray-500' }}">list_alt</span>
            <span class="text-sm" x-show="sidebarOpen" x-transition>Historial</span>
          </a>

          <a href="{{ route('compras.create') }}"
             class="group relative flex items-center gap-3 px-3 py-2 rounded-lg text-gray-600 hover:bg-gray-50 hover:text-gray-900">
            <span class="material-symbols-outlined mi text-gray-400 group-hover:text-gray-500">add_shopping_cart</span>
            <span class="text-sm" x-show="sidebarOpen" x-transition>Nueva compra</span>
          </a>
        </div>
      </section>
      @endif

      {{-- ===================== VENTAS ===================== --}}
      @if(auth()->user()?->hasAnyRole(['superadmin','administrador_empresa','gerente','vendedor']))
      <section class="space-y-1">
        <button type="button"
                class="group w-full flex items-center gap-2 rounded-lg text-[11px] font-semibold tracking-wide
                       text-gray-500 hover:text-gray-800 hover:bg-gray-50 px-3 py-2 transition-colors"
                :class="!sidebarOpen ? 'justify-center' : ''"
                @click="open.ventas = !open.ventas">
          <span class="material-symbols-outlined mi text-base">sell</span>
          <span class="uppercase" x-show="sidebarOpen" x-transition>VENTAS</span>
          <span class="ml-auto material-symbols-outlined mi text-sm transition-transform duration-200"
                x-show="sidebarOpen"
                :class="open.ventas ? 'rotate-0' : '-rotate-90'">expand_more</span>
        </button>

        <div x-show="open.ventas" x-collapse class="space-y-1" x-cloak>
          <a href="{{ route('ventas.index') }}"
             class="group relative flex items-center gap-3 px-3 py-2 rounded-lg {{ $isVentasActive ? 'bg-indigo-50 text-indigo-700 font-medium' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
            @if($isVentasActive)<span class="absolute left-0 top-1.5 bottom-1.5 w-1 bg-indigo-600 rounded-r-full"></span>@endif
            <span class="material-symbols-outlined mi {{ $isVentasActive ? 'text-indigo-600' : 'text-gray-400 group-hover:text-gray-500' }}">receipt</span>
            <span class="text-sm" x-show="sidebarOpen" x-transition>Todas las Ventas</span>
          </a>

          @php $isPref = request()->routeIs('ventas.index') && request('estatus') === 'prefactura'; @endphp
          <a href="{{ route('ventas.index', ['estatus' => 'prefactura']) }}"
             class="group relative flex items-center gap-3 px-3 py-2 rounded-lg {{ $isPref ? 'bg-indigo-50 text-indigo-700 font-medium' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
            @if($isPref)<span class="absolute left-0 top-1.5 bottom-1.5 w-1 bg-indigo-600 rounded-r-full"></span>@endif
            <span class="material-symbols-outlined mi {{ $isPref ? 'text-indigo-600' : 'text-gray-400 group-hover:text-gray-500' }}">pending_actions</span>
            <span class="text-sm" x-show="sidebarOpen" x-transition>Prefacturas</span>
            @if(!empty($kpis['prefacturasPendientes']))
              <span class="ml-auto inline-flex items-center justify-center min-w-[1.25rem] h-5 px-1.5 rounded-full bg-amber-100 text-amber-700 text-[10px] font-bold shadow-sm">
                {{ $kpis['prefacturasPendientes'] }}
              </span>
            @endif
          </a>

          <a href="{{ route('ventas.create') }}"
             class="group relative flex items-center gap-3 px-3 py-2 rounded-lg text-gray-600 hover:bg-gray-50 hover:text-gray-900">
            <span class="material-symbols-outlined mi text-gray-400 group-hover:text-gray-500">add_circle</span>
            <span class="text-sm" x-show="sidebarOpen" x-transition>Nueva Venta</span>
          </a>
        </div>
      </section>
      @endif

      {{-- ===================== MEMBRESÍA (Antes Facturación) ===================== --}}
      <section class="space-y-1">
        <button type="button"
                class="group w-full flex items-center gap-2 rounded-lg text-[11px] font-semibold tracking-wide
                       text-gray-500 hover:text-gray-800 hover:bg-gray-50 px-3 py-2 transition-colors"
                :class="!sidebarOpen ? 'justify-center' : ''"
                @click="open.membresia = !open.membresia">
          <span class="material-symbols-outlined mi text-base">card_membership</span>
          <span class="uppercase" x-show="sidebarOpen" x-transition>MEMBRESÍA</span>
          <span class="ml-auto material-symbols-outlined mi text-sm transition-transform duration-200"
                x-show="sidebarOpen"
                :class="open.membresia ? 'rotate-0' : '-rotate-90'">expand_more</span>
        </button>

        <div x-show="open.membresia" x-collapse class="space-y-1" x-cloak>
          <a href="{{ route('suscripciones.index') }}"
             class="group relative flex items-center gap-3 px-3 py-2 rounded-lg {{ $isActive('suscripciones.*') ? 'bg-indigo-50 text-indigo-700 font-medium' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
            @if($isActive('suscripciones.*'))<span class="absolute left-0 top-1.5 bottom-1.5 w-1 bg-indigo-600 rounded-r-full"></span>@endif
            <span class="material-symbols-outlined mi {{ $isActive('suscripciones.*') ? 'text-indigo-600' : 'text-gray-400 group-hover:text-gray-500' }}">loyalty</span>
            <span class="text-sm" x-show="sidebarOpen" x-transition>Suscripción</span>
          </a>
        </div>
      </section>

    </nav>
  </div>
</aside>