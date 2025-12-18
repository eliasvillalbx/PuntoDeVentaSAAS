{{-- resources/views/layouts/sidebar.blade.php --}}
@php
  $isActive = fn ($pattern) => request()->routeIs($pattern);

  // Verificaciones de estado para mantener menús abiertos
  $isVentasActive   = $isActive('ventas.*');
  $isComprasActive  = $isActive('compras.*');
  $isSistemaActive  = $isActive('backups.*') || $isActive('logs.*'); 
  
  // Nuevo: Verificación para Usuarios
  $isUsersActive    = $isActive('users.*');

  // KPIs o Badges
  $kpis = [
    'prefacturasPendientes' => $prefacturasPendientes ?? null,
  ];
@endphp

<aside
  x-data="{
    open: (JSON.parse(localStorage.getItem('sb_open') || '{}')) || {},
    init(){
      // Defaults: Configura qué secciones inician abiertas
      this.open.general     ??= true;
      this.open.personas    ??= false;
      this.open.catalogo    ??= false;
      this.open.compras     ??= false;
      this.open.ventas      ??= true;
      this.open.membresia   ??= false; 
      this.open.sistema     ??= false; 
    },
    persist(){ localStorage.setItem('sb_open', JSON.stringify(this.open)); }
  }"
  x-init="$watch('open', () => persist())"
  class="sticky top-16 h-[calc(100vh-4rem)] bg-white border-r border-gray-200 transition-all duration-300 ease-in-out z-30 flex flex-col"
  :class="sidebarOpen ? 'w-64' : 'w-20'"
  role="complementary"
>
  
  {{-- Header Colapsable --}}
  <div class="h-12 flex items-center justify-center border-b border-gray-100 bg-gray-50/50">
    <span class="text-[10px] font-extrabold text-gray-400 tracking-widest uppercase transition-opacity duration-200"
          x-show="sidebarOpen">
      Navegación
    </span>
    <span class="text-xs font-bold text-gray-400" x-show="!sidebarOpen">NV</span>
  </div>

  {{-- Scroll Area --}}
  <nav class="flex-1 overflow-y-auto overflow-x-hidden p-3 space-y-4 custom-scrollbar">

    {{-- ===================== GENERAL ===================== --}}
    <section class="space-y-1">
      <button type="button"
              class="group w-full flex items-center gap-3 rounded-lg text-[11px] font-bold tracking-wide text-gray-400 hover:text-indigo-600 hover:bg-indigo-50/50 px-3 py-2 transition-all"
              :class="!sidebarOpen ? 'justify-center' : ''"
              @click="open.general = !open.general">
        <span class="material-symbols-outlined text-[20px]">grid_view</span>
        <span class="uppercase flex-1 text-left" x-show="sidebarOpen" x-transition>GENERAL</span>
        <span class="material-symbols-outlined text-[16px] transition-transform duration-200"
              x-show="sidebarOpen"
              :class="open.general ? 'rotate-0' : '-rotate-90'">expand_more</span>
      </button>

      <div x-show="open.general" x-collapse class="space-y-1 pl-1" x-cloak>
        {{-- Dashboard --}}
        <a href="{{ route('dashboard') }}"
           class="group relative flex items-center gap-3 px-3 py-2 rounded-md transition-colors {{ $isActive('dashboard') ? 'bg-indigo-50 text-indigo-700 font-medium' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
          @if($isActive('dashboard'))<div class="absolute left-0 h-6 w-1 bg-indigo-600 rounded-r-full"></div>@endif
          <span class="material-symbols-outlined text-[20px] {{ $isActive('dashboard') ? 'text-indigo-600' : 'text-gray-400 group-hover:text-gray-500' }}">home</span>
          <span class="text-sm truncate" x-show="sidebarOpen">Dashboard</span>
        </a>

        {{-- Calendario --}}
        <a href="{{ route('calendar.index') }}"
           class="group relative flex items-center gap-3 px-3 py-2 rounded-md transition-colors {{ $isActive('calendar.*') ? 'bg-indigo-50 text-indigo-700 font-medium' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
           @if($isActive('calendar.*'))<div class="absolute left-0 h-6 w-1 bg-indigo-600 rounded-r-full"></div>@endif
          <span class="material-symbols-outlined text-[20px] {{ $isActive('calendar.*') ? 'text-indigo-600' : 'text-gray-400 group-hover:text-gray-500' }}">calendar_month</span>
          <span class="text-sm truncate" x-show="sidebarOpen">Calendario</span>
        </a>

        {{-- Empresas --}}
        <a href="{{ route('empresas.index') }}"
           class="group relative flex items-center gap-3 px-3 py-2 rounded-md transition-colors {{ $isActive('empresas.*') ? 'bg-indigo-50 text-indigo-700 font-medium' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
           @if($isActive('empresas.*'))<div class="absolute left-0 h-6 w-1 bg-indigo-600 rounded-r-full"></div>@endif
          <span class="material-symbols-outlined text-[20px] {{ $isActive('empresas.*') ? 'text-indigo-600' : 'text-gray-400 group-hover:text-gray-500' }}">domain</span>
          <span class="text-sm truncate" x-show="sidebarOpen">Empresas</span>
        </a>
      </div>
    </section>

    {{-- ===================== PERSONAS ===================== --}}
    @if(auth()->user()?->hasAnyRole(['superadmin','administrador_empresa','gerente']))
    <section class="space-y-1">
      <button type="button"
              class="group w-full flex items-center gap-3 rounded-lg text-[11px] font-bold tracking-wide text-gray-400 hover:text-indigo-600 hover:bg-indigo-50/50 px-3 py-2 transition-all"
              :class="!sidebarOpen ? 'justify-center' : ''"
              @click="open.personas = !open.personas">
        <span class="material-symbols-outlined text-[20px]">groups</span>
        <span class="uppercase flex-1 text-left" x-show="sidebarOpen" x-transition>PERSONAS</span>
        <span class="material-symbols-outlined text-[16px] transition-transform duration-200"
              x-show="sidebarOpen"
              :class="open.personas ? 'rotate-0' : '-rotate-90'">expand_more</span>
      </button>

      <div x-show="open.personas" x-collapse class="space-y-1 pl-1" x-cloak>
        
        {{-- NUEVO: Gestión de Usuarios --}}
        <a href="{{ route('users.index') }}"
           class="group relative flex items-center gap-3 px-3 py-2 rounded-md transition-colors {{ $isUsersActive ? 'bg-indigo-50 text-indigo-700 font-medium' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
           @if($isUsersActive)<div class="absolute left-0 h-6 w-1 bg-indigo-600 rounded-r-full"></div>@endif
          <span class="material-symbols-outlined text-[20px] {{ $isUsersActive ? 'text-indigo-600' : 'text-gray-400 group-hover:text-gray-500' }}">group</span>
          <span class="text-sm truncate" x-show="sidebarOpen">Usuarios</span>
        </a>

        @role('superadmin')
        <a href="{{ route('admin-empresas.index') }}"
           class="group relative flex items-center gap-3 px-3 py-2 rounded-md transition-colors {{ $isActive('admin-empresas.*') ? 'bg-indigo-50 text-indigo-700 font-medium' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
           @if($isActive('admin-empresas.*'))<div class="absolute left-0 h-6 w-1 bg-indigo-600 rounded-r-full"></div>@endif
          <span class="material-symbols-outlined text-[20px] {{ $isActive('admin-empresas.*') ? 'text-indigo-600' : 'text-gray-400 group-hover:text-gray-500' }}">verified_user</span>
          <span class="text-sm truncate" x-show="sidebarOpen">Administradores</span>
        </a>
        @endrole

        <a href="{{ route('gerentes.index') }}"
           class="group relative flex items-center gap-3 px-3 py-2 rounded-md transition-colors {{ $isActive('gerentes.*') ? 'bg-indigo-50 text-indigo-700 font-medium' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
           @if($isActive('gerentes.*'))<div class="absolute left-0 h-6 w-1 bg-indigo-600 rounded-r-full"></div>@endif
          <span class="material-symbols-outlined text-[20px] {{ $isActive('gerentes.*') ? 'text-indigo-600' : 'text-gray-400 group-hover:text-gray-500' }}">manage_accounts</span>
          <span class="text-sm truncate" x-show="sidebarOpen">Gerentes</span>
        </a>

        <a href="{{ route('vendedores.index') }}"
           class="group relative flex items-center gap-3 px-3 py-2 rounded-md transition-colors {{ $isActive('vendedores.*') ? 'bg-indigo-50 text-indigo-700 font-medium' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
           @if($isActive('vendedores.*'))<div class="absolute left-0 h-6 w-1 bg-indigo-600 rounded-r-full"></div>@endif
          <span class="material-symbols-outlined text-[20px] {{ $isActive('vendedores.*') ? 'text-indigo-600' : 'text-gray-400 group-hover:text-gray-500' }}">badge</span>
          <span class="text-sm truncate" x-show="sidebarOpen">Vendedores</span>
        </a>

        <a href="{{ route('clientes.index') }}"
           class="group relative flex items-center gap-3 px-3 py-2 rounded-md transition-colors {{ $isActive('clientes.*') ? 'bg-indigo-50 text-indigo-700 font-medium' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
           @if($isActive('clientes.*'))<div class="absolute left-0 h-6 w-1 bg-indigo-600 rounded-r-full"></div>@endif
          <span class="material-symbols-outlined text-[20px] {{ $isActive('clientes.*') ? 'text-indigo-600' : 'text-gray-400 group-hover:text-gray-500' }}">sentiment_satisfied</span>
          <span class="text-sm truncate" x-show="sidebarOpen">Clientes</span>
        </a>
      </div>
    </section>
    @endif

    {{-- ===================== CATÁLOGO ===================== --}}
    @if(auth()->user()?->hasAnyRole(['superadmin','administrador_empresa','gerente']))
    <section class="space-y-1">
      <button type="button"
              class="group w-full flex items-center gap-3 rounded-lg text-[11px] font-bold tracking-wide text-gray-400 hover:text-indigo-600 hover:bg-indigo-50/50 px-3 py-2 transition-all"
              :class="!sidebarOpen ? 'justify-center' : ''"
              @click="open.catalogo = !open.catalogo">
        <span class="material-symbols-outlined text-[20px]">inventory_2</span>
        <span class="uppercase flex-1 text-left" x-show="sidebarOpen" x-transition>CATÁLOGO</span>
        <span class="material-symbols-outlined text-[16px] transition-transform duration-200"
              x-show="sidebarOpen"
              :class="open.catalogo ? 'rotate-0' : '-rotate-90'">expand_more</span>
      </button>

      <div x-show="open.catalogo" x-collapse class="space-y-1 pl-1" x-cloak>
        <a href="{{ route('productos.index') }}"
           class="group relative flex items-center gap-3 px-3 py-2 rounded-md transition-colors {{ $isActive('productos.*') ? 'bg-indigo-50 text-indigo-700 font-medium' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
           @if($isActive('productos.*'))<div class="absolute left-0 h-6 w-1 bg-indigo-600 rounded-r-full"></div>@endif
          <span class="material-symbols-outlined text-[20px] {{ $isActive('productos.*') ? 'text-indigo-600' : 'text-gray-400 group-hover:text-gray-500' }}">package_2</span>
          <span class="text-sm truncate" x-show="sidebarOpen">Productos</span>
        </a>

        <a href="{{ route('categorias.index') }}"
           class="group relative flex items-center gap-3 px-3 py-2 rounded-md transition-colors {{ $isActive('categorias.*') ? 'bg-indigo-50 text-indigo-700 font-medium' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
           @if($isActive('categorias.*'))<div class="absolute left-0 h-6 w-1 bg-indigo-600 rounded-r-full"></div>@endif
          <span class="material-symbols-outlined text-[20px] {{ $isActive('categorias.*') ? 'text-indigo-600' : 'text-gray-400 group-hover:text-gray-500' }}">category</span>
          <span class="text-sm truncate" x-show="sidebarOpen">Categorías</span>
        </a>

        <a href="{{ route('proveedores.index') }}"
           class="group relative flex items-center gap-3 px-3 py-2 rounded-md transition-colors {{ $isActive('proveedores.*') ? 'bg-indigo-50 text-indigo-700 font-medium' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
           @if($isActive('proveedores.*'))<div class="absolute left-0 h-6 w-1 bg-indigo-600 rounded-r-full"></div>@endif
          <span class="material-symbols-outlined text-[20px] {{ $isActive('proveedores.*') ? 'text-indigo-600' : 'text-gray-400 group-hover:text-gray-500' }}">local_shipping</span>
          <span class="text-sm truncate" x-show="sidebarOpen">Proveedores</span>
        </a>
      </div>
    </section>
    @endif

    {{-- ===================== COMPRAS ===================== --}}
    @if(auth()->user()?->hasAnyRole(['superadmin','administrador_empresa','gerente']))
    <section class="space-y-1">
      <button type="button"
              class="group w-full flex items-center gap-3 rounded-lg text-[11px] font-bold tracking-wide text-gray-400 hover:text-indigo-600 hover:bg-indigo-50/50 px-3 py-2 transition-all"
              :class="!sidebarOpen ? 'justify-center' : ''"
              @click="open.compras = !open.compras">
        <span class="material-symbols-outlined text-[20px]">shopping_basket</span>
        <span class="uppercase flex-1 text-left" x-show="sidebarOpen" x-transition>COMPRAS</span>
        <span class="material-symbols-outlined text-[16px] transition-transform duration-200"
              x-show="sidebarOpen"
              :class="open.compras ? 'rotate-0' : '-rotate-90'">expand_more</span>
      </button>

      <div x-show="open.compras" x-collapse class="space-y-1 pl-1" x-cloak>
        <a href="{{ route('compras.index') }}"
           class="group relative flex items-center gap-3 px-3 py-2 rounded-md transition-colors {{ $isComprasActive ? 'bg-indigo-50 text-indigo-700 font-medium' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
           @if($isComprasActive)<div class="absolute left-0 h-6 w-1 bg-indigo-600 rounded-r-full"></div>@endif
          <span class="material-symbols-outlined text-[20px] {{ $isComprasActive ? 'text-indigo-600' : 'text-gray-400 group-hover:text-gray-500' }}">list_alt</span>
          <span class="text-sm truncate" x-show="sidebarOpen">Historial</span>
        </a>

        <a href="{{ route('compras.create') }}"
           class="group relative flex items-center gap-3 px-3 py-2 rounded-md transition-colors text-gray-600 hover:bg-gray-50 hover:text-gray-900">
          <span class="material-symbols-outlined text-[20px] text-gray-400 group-hover:text-gray-500">add_shopping_cart</span>
          <span class="text-sm truncate" x-show="sidebarOpen">Nueva compra</span>
        </a>
      </div>
    </section>
    @endif

    {{-- ===================== VENTAS ===================== --}}
    @if(auth()->user()?->hasAnyRole(['superadmin','administrador_empresa','gerente','vendedor']))
    <section class="space-y-1">
      <button type="button"
              class="group w-full flex items-center gap-3 rounded-lg text-[11px] font-bold tracking-wide text-gray-400 hover:text-indigo-600 hover:bg-indigo-50/50 px-3 py-2 transition-all"
              :class="!sidebarOpen ? 'justify-center' : ''"
              @click="open.ventas = !open.ventas">
        <span class="material-symbols-outlined text-[20px]">sell</span>
        <span class="uppercase flex-1 text-left" x-show="sidebarOpen" x-transition>VENTAS</span>
        <span class="material-symbols-outlined text-[16px] transition-transform duration-200"
              x-show="sidebarOpen"
              :class="open.ventas ? 'rotate-0' : '-rotate-90'">expand_more</span>
      </button>

      <div x-show="open.ventas" x-collapse class="space-y-1 pl-1" x-cloak>
        <a href="{{ route('ventas.index') }}"
           class="group relative flex items-center gap-3 px-3 py-2 rounded-md transition-colors {{ $isVentasActive ? 'bg-indigo-50 text-indigo-700 font-medium' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
           @if($isVentasActive)<div class="absolute left-0 h-6 w-1 bg-indigo-600 rounded-r-full"></div>@endif
          <span class="material-symbols-outlined text-[20px] {{ $isVentasActive ? 'text-indigo-600' : 'text-gray-400 group-hover:text-gray-500' }}">receipt</span>
          <span class="text-sm truncate" x-show="sidebarOpen">Todas las Ventas</span>
        </a>

        @php $isPref = request()->routeIs('ventas.index') && request('estatus') === 'prefactura'; @endphp
        <a href="{{ route('ventas.index', ['estatus' => 'prefactura']) }}"
           class="group relative flex items-center gap-3 px-3 py-2 rounded-md transition-colors {{ $isPref ? 'bg-indigo-50 text-indigo-700 font-medium' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
           @if($isPref)<div class="absolute left-0 h-6 w-1 bg-indigo-600 rounded-r-full"></div>@endif
          <span class="material-symbols-outlined text-[20px] {{ $isPref ? 'text-indigo-600' : 'text-gray-400 group-hover:text-gray-500' }}">pending_actions</span>
          <span class="text-sm truncate flex-1" x-show="sidebarOpen">Prefacturas</span>
          
          @if(!empty($kpis['prefacturasPendientes']) && $kpis['prefacturasPendientes'] > 0)
            <span x-show="sidebarOpen" class="inline-flex items-center justify-center h-5 px-2 rounded-full bg-amber-100 text-amber-700 text-[10px] font-bold shadow-sm">
              {{ $kpis['prefacturasPendientes'] }}
            </span>
            <span x-show="!sidebarOpen" class="absolute top-1 right-1 h-2 w-2 rounded-full bg-amber-500"></span>
          @endif
        </a>

        <a href="{{ route('ventas.create') }}"
           class="group relative flex items-center gap-3 px-3 py-2 rounded-md transition-colors text-gray-600 hover:bg-gray-50 hover:text-gray-900">
          <span class="material-symbols-outlined text-[20px] text-gray-400 group-hover:text-gray-500">add_circle</span>
          <span class="text-sm truncate" x-show="sidebarOpen">Nueva Venta</span>
        </a>
      </div>
    </section>
    @endif

    {{-- ===================== MEMBRESÍA ===================== --}}
    <section class="space-y-1">
      <button type="button"
              class="group w-full flex items-center gap-3 rounded-lg text-[11px] font-bold tracking-wide text-gray-400 hover:text-indigo-600 hover:bg-indigo-50/50 px-3 py-2 transition-all"
              :class="!sidebarOpen ? 'justify-center' : ''"
              @click="open.membresia = !open.membresia">
        <span class="material-symbols-outlined text-[20px]">card_membership</span>
        <span class="uppercase flex-1 text-left" x-show="sidebarOpen" x-transition>MEMBRESÍA</span>
        <span class="material-symbols-outlined text-[16px] transition-transform duration-200"
              x-show="sidebarOpen"
              :class="open.membresia ? 'rotate-0' : '-rotate-90'">expand_more</span>
      </button>

      <div x-show="open.membresia" x-collapse class="space-y-1 pl-1" x-cloak>
        <a href="{{ route('suscripciones.index') }}"
           class="group relative flex items-center gap-3 px-3 py-2 rounded-md transition-colors {{ $isActive('suscripciones.*') ? 'bg-indigo-50 text-indigo-700 font-medium' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
           @if($isActive('suscripciones.*'))<div class="absolute left-0 h-6 w-1 bg-indigo-600 rounded-r-full"></div>@endif
          <span class="material-symbols-outlined text-[20px] {{ $isActive('suscripciones.*') ? 'text-indigo-600' : 'text-gray-400 group-hover:text-gray-500' }}">loyalty</span>
          <span class="text-sm truncate" x-show="sidebarOpen">Suscripción</span>
        </a>
      </div>
    </section>

    {{-- ===================== SISTEMA (NUEVO) ===================== --}}
    @if(auth()->user()?->hasAnyRole(['superadmin','administrador_empresa']))
    <div class="pt-4 mt-2 border-t border-gray-100">
      <section class="space-y-1">
        <button type="button"
                class="group w-full flex items-center gap-3 rounded-lg text-[11px] font-bold tracking-wide text-gray-400 hover:text-indigo-600 hover:bg-indigo-50/50 px-3 py-2 transition-all"
                :class="!sidebarOpen ? 'justify-center' : ''"
                @click="open.sistema = !open.sistema">
          <span class="material-symbols-outlined text-[20px]">settings</span>
          <span class="uppercase flex-1 text-left" x-show="sidebarOpen" x-transition>SISTEMA</span>
          <span class="material-symbols-outlined text-[16px] transition-transform duration-200"
                x-show="sidebarOpen"
                :class="open.sistema ? 'rotate-0' : '-rotate-90'">expand_more</span>
        </button>

        <div x-show="open.sistema" x-collapse class="space-y-1 pl-1" x-cloak>
          {{-- Backups --}}
          <a href="{{ route('backups.index') }}"
             class="group relative flex items-center gap-3 px-3 py-2 rounded-md transition-colors {{ $isActive('backups.*') ? 'bg-indigo-50 text-indigo-700 font-medium' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
             @if($isActive('backups.*'))<div class="absolute left-0 h-6 w-1 bg-indigo-600 rounded-r-full"></div>@endif
            <span class="material-symbols-outlined text-[20px] {{ $isActive('backups.*') ? 'text-indigo-600' : 'text-gray-400 group-hover:text-gray-500' }}">cloud_sync</span>
            <span class="text-sm truncate" x-show="sidebarOpen">Respaldos BD</span>
          </a>
        </div>
      </section>
    </div>
    @endif

  </nav>
</aside>