<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-bold text-slate-800 flex items-center gap-2">
                <span class="material-symbols-outlined text-indigo-600">space_dashboard</span>
                Panel de Control {{ $isSA ? '(Vista SuperAdmin)' : '' }}
            </h2>
            <span class="text-sm text-slate-500 bg-white px-3 py-1 rounded-full border border-slate-200 shadow-sm">
                {{ now()->format('d \d\e F, Y') }}
            </span>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto space-y-6 pb-12">

        {{-- ========================================================== --}}
        {{-- SECCIÓN A: ALERTA DE SUSCRIPCIONES (LÓGICA MIXTA) --}}
        {{-- ========================================================== --}}

        {{-- 1. PARA SUPER ADMIN (FN.17 Resumen Global) --}}
        @if($isSA)
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-indigo-900 text-white rounded-xl p-4 flex items-center justify-between shadow-sm">
                    <div>
                        <p class="text-xs font-bold opacity-70 uppercase">Empresas Activas</p>
                        <p class="text-2xl font-black">{{ $resumenSuscripciones['activas'] ?? 0 }}</p>
                    </div>
                    <span class="material-symbols-outlined text-4xl opacity-50">domain</span>
                </div>
                <div class="bg-slate-800 text-white rounded-xl p-4 flex items-center justify-between shadow-sm">
                    <div>
                        <p class="text-xs font-bold opacity-70 uppercase">Empresas Inactivas</p>
                        <p class="text-2xl font-black">{{ $resumenSuscripciones['inactivas'] ?? 0 }}</p>
                    </div>
                    <span class="material-symbols-outlined text-4xl opacity-50">domain_disabled</span>
                </div>
                <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-sm">
                    <p class="text-xs font-bold text-slate-500 uppercase mb-2">Próximos Vencimientos</p>
                    <ul class="space-y-2">
                        @forelse($resumenSuscripciones['proximas_vencer'] as $sub)
                            <li class="flex justify-between text-xs">
                                <span class="text-slate-700 truncate w-32">{{ $sub->empresa->razon_social }}</span>
                                <span class="text-red-600 font-bold">{{ $sub->fecha_vencimiento->format('d/m') }}</span>
                            </li>
                        @empty
                            <li class="text-xs text-slate-400 italic">No hay vencimientos próximos (7 días)</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        @endif

        {{-- 2. PARA USUARIO NORMAL (Su propia suscripción) --}}
        @if(!$isSA && $suscripcion && ($suscripcion->estado === 'vencida' || $suscripcion->fecha_vencimiento->diffInDays(now()) < 5))
            <div class="rounded-xl border-l-4 p-4 shadow-sm flex items-start gap-3 
                {{ $suscripcion->estado === 'vencida' ? 'bg-red-50 border-red-500' : 'bg-amber-50 border-amber-500' }}">
                <span class="material-symbols-outlined {{ $suscripcion->estado === 'vencida' ? 'text-red-600' : 'text-amber-600' }}">
                    {{ $suscripcion->estado === 'vencida' ? 'block' : 'timer' }}
                </span>
                <div>
                    <h3 class="font-bold text-sm {{ $suscripcion->estado === 'vencida' ? 'text-red-800' : 'text-amber-800' }}">
                        Estado de la Suscripción: {{ ucfirst($suscripcion->estado) }}
                    </h3>
                    <p class="text-xs mt-1 {{ $suscripcion->estado === 'vencida' ? 'text-red-700' : 'text-amber-700' }}">
                        Tu plan vence el {{ $suscripcion->fecha_vencimiento->format('d/m/Y') }}.
                    </p>
                </div>
            </div>
        @endif


        {{-- ========================================================== --}}
        {{-- SECCIÓN B: TARJETAS KPI (Finanzas + Inventario) --}}
        {{-- ========================================================== --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            
            {{-- Ingresos --}}
            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
                <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Ingresos (Mes)</p>
                <p class="text-2xl font-black text-slate-900 mt-1">${{ number_format($ventasMes, 2) }}</p>
                <div class="mt-2 flex items-center gap-1 text-xs font-medium text-indigo-600">
                    <span class="material-symbols-outlined text-sm">trending_up</span> Ventas pagadas
                </div>
            </div>

            {{-- Egresos --}}
            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
                <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Egresos (Mes)</p>
                <p class="text-2xl font-black text-slate-900 mt-1">${{ number_format($comprasMes, 2) }}</p>
                <div class="mt-2 flex items-center gap-1 text-xs font-medium text-rose-600">
                    <span class="material-symbols-outlined text-sm">payments</span> Compras
                </div>
            </div>

            {{-- Utilidad --}}
            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
                <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Utilidad Neta Aprox.</p>
                <p class="text-2xl font-black {{ $balance >= 0 ? 'text-emerald-600' : 'text-red-600' }} mt-1">
                    ${{ number_format($balance, 2) }}
                </p>
                <div class="mt-2 flex items-center gap-1 text-xs font-medium text-slate-500">
                    <span class="material-symbols-outlined text-sm">account_balance</span> Ingresos - Egresos
                </div>
            </div>

            {{-- Valor Inventario (FN.19) --}}
            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
                <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Valor Inventario</p>
                <p class="text-2xl font-black text-slate-900 mt-1">${{ number_format($valorInventario, 2) }}</p>
                <div class="mt-2 flex items-center gap-1 text-xs font-medium text-amber-600">
                    <span class="material-symbols-outlined text-sm">inventory_2</span> Costo Referencial
                </div>
            </div>
        </div>


        {{-- ========================================================== --}}
        {{-- SECCIÓN C: TABLAS DE ANÁLISIS (FN.16, FN.18, FN.20) --}}
        {{-- ========================================================== --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            {{-- 1. PRODUCTOS ESTRELLA (FN.16) --}}
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="p-4 border-b border-slate-100 flex items-center gap-2 bg-slate-50/50">
                    <span class="material-symbols-outlined text-amber-500">trophy</span>
                    <h3 class="font-bold text-slate-800 text-sm">Top Productos (Mes)</h3>
                </div>
                <div class="divide-y divide-slate-100">
                    @forelse($topProductos as $prod)
                        <div class="p-3 flex justify-between items-center hover:bg-slate-50">
                            <div>
                                <p class="text-xs font-bold text-slate-800 line-clamp-1">{{ $prod->nombre }}</p>
                                <p class="text-[10px] text-slate-500">
                                    Ganancia Est: <span class="text-emerald-600 font-bold">${{ number_format($prod->ganancia_estimada) }}</span>
                                </p>
                            </div>
                            <div class="text-right">
                                <span class="block text-xs font-bold text-slate-900">{{ $prod->total_vendido }} un.</span>
                                <span class="block text-[10px] text-slate-400">${{ number_format($prod->dinero_generado) }}</span>
                            </div>
                        </div>
                    @empty
                        <div class="p-6 text-center text-xs text-slate-400">Sin datos de ventas.</div>
                    @endforelse
                </div>
            </div>

            {{-- 2. MEJORES CLIENTES (FN.18) --}}
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="p-4 border-b border-slate-100 flex items-center gap-2 bg-slate-50/50">
                    <span class="material-symbols-outlined text-blue-500">group_add</span>
                    <h3 class="font-bold text-slate-800 text-sm">Mejores Clientes</h3>
                </div>
                <div class="divide-y divide-slate-100">
                    @forelse($topClientes as $cte)
                        <div class="p-3 flex justify-between items-center hover:bg-slate-50">
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 rounded-full bg-blue-50 text-blue-600 grid place-items-center text-xs font-bold">
                                    {{ substr($cte->nombre ?: $cte->razon_social, 0, 1) }}
                                </div>
                                <div>
                                    <p class="text-xs font-bold text-slate-800 line-clamp-1">
                                        {{ $cte->tipo_persona == 'fisica' ? $cte->nombre . ' ' . $cte->apellido_paterno : $cte->razon_social }}
                                    </p>
                                    <p class="text-[10px] text-slate-500">{{ $cte->compras_realizadas }} compras</p>
                                </div>
                            </div>
                            <span class="text-xs font-bold text-slate-900">${{ number_format($cte->total_gastado) }}</span>
                        </div>
                    @empty
                        <div class="p-6 text-center text-xs text-slate-400">Sin clientes destacados.</div>
                    @endforelse
                </div>
            </div>

            {{-- 3. PROVEEDORES PRINCIPALES (FN.20) --}}
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="p-4 border-b border-slate-100 flex items-center gap-2 bg-slate-50/50">
                    <span class="material-symbols-outlined text-rose-500">local_shipping</span>
                    <h3 class="font-bold text-slate-800 text-sm">Top Proveedores</h3>
                </div>
                <div class="divide-y divide-slate-100">
                    @forelse($topProveedores as $prov)
                        <div class="p-3 flex justify-between items-center hover:bg-slate-50">
                            <div>
                                <p class="text-xs font-bold text-slate-800 line-clamp-1">{{ $prov->nombre }}</p>
                                <p class="text-[10px] text-slate-500">{{ $prov->ordenes_hechas }} órdenes</p>
                            </div>
                            <span class="text-xs font-bold text-slate-900">${{ number_format($prov->total_pagado) }}</span>
                        </div>
                    @empty
                        <div class="p-6 text-center text-xs text-slate-400">Sin compras recientes.</div>
                    @endforelse
                </div>
            </div>

        </div>


        {{-- ========================================================== --}}
        {{-- SECCIÓN D: OPERATIVIDAD DIARIA (Últimas Ventas y Stock) --}}
        {{-- ========================================================== --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            {{-- Últimas Ventas (2/3) --}}
            <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                    <h3 class="font-bold text-slate-800 flex items-center gap-2">
                        <span class="material-symbols-outlined text-slate-400">receipt_long</span>
                        Actividad Reciente
                    </h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-slate-50 text-slate-500 font-semibold border-b border-slate-200">
                            <tr>
                                <th class="px-5 py-3">Folio</th>
                                <th class="px-5 py-3">Cliente</th>
                                <th class="px-5 py-3">Total</th>
                                <th class="px-5 py-3 text-right">Hora</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($ultimasVentas as $venta)
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-5 py-3 font-medium text-slate-900">#{{ $venta->id }}</td>
                                <td class="px-5 py-3 text-slate-600">
                                    {{ $venta->cliente?->nombre_mostrar ?? 'Público General' }}
                                </td>
                                <td class="px-5 py-3 font-bold text-slate-800">${{ number_format($venta->total, 2) }}</td>
                                <td class="px-5 py-3 text-right text-slate-400 text-xs font-mono">
                                    {{ $venta->created_at->format('H:i') }}
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="p-8 text-center text-slate-500">Sin actividad hoy.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Alertas de Stock y Accesos (1/3) --}}
            <div class="lg:col-span-1 space-y-6">
                
                {{-- Stock Bajo --}}
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="p-4 bg-red-50 border-b border-red-100 flex items-center justify-between">
                        <h3 class="font-bold text-red-800 flex items-center gap-2 text-sm">
                            <span class="material-symbols-outlined text-base">warning</span>
                            Stock Crítico (<= 5)
                        </h3>
                    </div>
                    <div class="divide-y divide-slate-100">
                        @forelse($stockBajo as $prod)
                            <div class="p-3 flex justify-between items-center hover:bg-slate-50">
                                <p class="text-xs font-bold text-slate-800 truncate w-2/3">{{ $prod->nombre }}</p>
                                <span class="text-xs font-black text-red-600">{{ $prod->stock }} un.</span>
                            </div>
                        @empty
                            <div class="p-4 text-center text-xs text-slate-400">Inventario OK.</div>
                        @endforelse
                    </div>
                </div>

                {{-- Accesos Rápidos (Widget Oscuro) --}}
                <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl shadow-lg p-5 text-white">
                     <h3 class="font-bold text-sm mb-4 flex items-center gap-2">
                        <span class="material-symbols-outlined text-base">bolt</span>
                        Acciones Rápidas
                    </h3>
                    <div class="grid grid-cols-2 gap-3">
                        @if(Route::has('ventas.create'))
                            <a href="{{ route('ventas.create') }}" class="btn-quick-action flex flex-col items-center justify-center p-3 bg-white/10 hover:bg-white/20 rounded-xl transition-colors border border-white/5">
                                <span class="material-symbols-outlined mb-1">add_shopping_cart</span>
                                <span class="text-[10px]">Venta</span>
                            </a>
                        @endif
                        @if(Route::has('clientes.create'))
                            <a href="{{ route('clientes.create') }}" class="btn-quick-action flex flex-col items-center justify-center p-3 bg-white/10 hover:bg-white/20 rounded-xl transition-colors border border-white/5">
                                <span class="material-symbols-outlined mb-1">person_add</span>
                                <span class="text-[10px]">Cliente</span>
                            </a>
                        @endif
                         @if(Route::has('compras.create'))
                            <a href="{{ route('compras.create') }}" class="btn-quick-action flex flex-col items-center justify-center p-3 bg-white/10 hover:bg-white/20 rounded-xl transition-colors border border-white/5">
                                <span class="material-symbols-outlined mb-1">inventory</span>
                                <span class="text-[10px]">Compra</span>
                            </a>
                        @endif
                         @if(Route::has('productos.create'))
                            <a href="{{ route('productos.create') }}" class="btn-quick-action flex flex-col items-center justify-center p-3 bg-white/10 hover:bg-white/20 rounded-xl transition-colors border border-white/5">
                                <span class="material-symbols-outlined mb-1">add_box</span>
                                <span class="text-[10px]">Producto</span>
                            </a>
                        @endif
                    </div>
                </div>

            </div>
        </div>

    </div>
</x-app-layout>