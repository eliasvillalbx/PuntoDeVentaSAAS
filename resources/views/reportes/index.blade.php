<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row justify-between items-center gap-4">
            <h2 class="text-xl font-bold text-slate-800 flex items-center gap-2">
                <span class="material-symbols-outlined text-indigo-600">analytics</span>
                Reportes y Analítica
            </h2>
            
            @if($isSA)
                <form action="{{ route('reportes.index') }}" method="GET" class="flex items-center gap-2 bg-white px-4 py-2 rounded-lg shadow-sm border border-slate-200">
                    <span class="material-symbols-outlined text-slate-400">filter_alt</span>
                    <select name="empresa_id" onchange="this.form.submit()" class="border-none focus:ring-0 text-slate-700 text-sm font-bold bg-transparent">
                        <option value="">-- Seleccionar Empresa --</option>
                        @foreach($empresas as $emp)
                            <option value="{{ $emp->id }}" {{ $empresaIdSeleccionada == $emp->id ? 'selected' : '' }}>
                                {{ $emp->razon_social }}
                            </option>
                        @endforeach
                    </select>
                </form>
            @endif
        </div>
    </x-slot>

    <div class="py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">

        {{-- ========================================================= --}}
        {{-- FN.17: SUSCRIPCIONES (SOLO SA) --}}
        {{-- ========================================================= --}}
        @if($isSA)
        <section>
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-slate-800 border-l-4 border-indigo-500 pl-3">FN.17 Reporte de Suscripciones</h3>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200 grid grid-cols-1 lg:grid-cols-3 gap-8">
                {{-- Formulario Filtros --}}
                <div class="lg:col-span-1 space-y-4 border-r border-slate-100 pr-4">
                    <h4 class="font-bold text-sm text-slate-500 uppercase">Generar Reporte PDF</h4>
                    <form action="{{ route('reportes.suscripciones') }}" method="GET" class="space-y-3">
                        <div>
                            <label class="text-xs font-bold text-slate-600">Estado</label>
                            <select name="estado" class="w-full text-xs rounded border-slate-300">
                                <option value="">Todos</option>
                                <option value="activa">Activas</option>
                                <option value="vencida">Vencidas</option>
                            </select>
                        </div>
                        <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white py-2 rounded text-xs font-bold flex items-center justify-center gap-2">
                            <span class="material-symbols-outlined text-base">picture_as_pdf</span> Descargar Reporte
                        </button>
                    </form>
                </div>
                {{-- Gráficos --}}
                <div class="lg:col-span-2 grid grid-cols-2 gap-4">
                    <div class="h-48 relative"><canvas id="chartSaasEmpresas"></canvas></div>
                    <div class="h-48 relative"><canvas id="chartSaasSubs"></canvas></div>
                </div>
            </div>
        </section>
        @endif


        {{-- ========================================================= --}}
        {{-- ZONA OPERATIVA (FN.16 - FN.20) --}}
        {{-- ========================================================= --}}
        @if($mostrarDatos)

            {{-- FN.16: RENTABILIDAD --}}
            <section>
                <h3 class="text-lg font-bold text-slate-800 border-l-4 border-emerald-500 pl-3 mb-4">FN.16 Rentabilidad por Producto</h3>
                <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200 grid grid-cols-1 lg:grid-cols-3 gap-8">
                    {{-- Formulario --}}
                    <div class="lg:col-span-1 border-r border-slate-100 pr-4">
                        <form action="{{ route('reportes.rentabilidad') }}" method="GET" class="space-y-3">
                            @if($isSA) <input type="hidden" name="empresa_id" value="{{ $empresaIdSeleccionada }}"> @endif
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="text-[10px] font-bold text-slate-500">Desde</label>
                                    <input type="date" name="fecha_inicio" class="w-full text-xs rounded border-slate-300">
                                </div>
                                <div>
                                    <label class="text-[10px] font-bold text-slate-500">Hasta</label>
                                    <input type="date" name="fecha_fin" class="w-full text-xs rounded border-slate-300">
                                </div>
                            </div>
                            <div>
                                <label class="text-[10px] font-bold text-slate-500">Categoría</label>
                                <select name="categoria_id" class="w-full text-xs rounded border-slate-300">
                                    <option value="">Todas</option>
                                    @foreach($categorias as $cat)
                                        <option value="{{ $cat->id }}">{{ $cat->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="text-[10px] font-bold text-slate-500">Margen Mínimo (%)</label>
                                <input type="number" name="margen_minimo" placeholder="Ej: 20" class="w-full text-xs rounded border-slate-300">
                            </div>
                            <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white py-2 rounded text-xs font-bold flex items-center justify-center gap-2">
                                <span class="material-symbols-outlined text-base">picture_as_pdf</span> Exportar Rentabilidad
                            </button>
                        </form>
                    </div>
                    {{-- Gráfico --}}
                    <div class="lg:col-span-2 h-64 relative">
                        @if($rentabilidad->isEmpty()) <p class="text-center text-slate-400 mt-10">Sin ventas registradas.</p> @else <canvas id="chartRentabilidad"></canvas> @endif
                    </div>
                </div>
            </section>

            {{-- FN.18: CLIENTES --}}
            <section>
                <h3 class="text-lg font-bold text-slate-800 border-l-4 border-blue-500 pl-3 mb-4">FN.18 Análisis de Clientes</h3>
                <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200 grid grid-cols-1 lg:grid-cols-3 gap-8">
                    {{-- Formulario --}}
                    <div class="lg:col-span-1 border-r border-slate-100 pr-4">
                        <form action="{{ route('reportes.clientes') }}" method="GET" class="space-y-3">
                            @if($isSA) <input type="hidden" name="empresa_id" value="{{ $empresaIdSeleccionada }}"> @endif
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="text-[10px] font-bold text-slate-500">Desde</label>
                                    <input type="date" name="fecha_inicio" class="w-full text-xs rounded border-slate-300">
                                </div>
                                <div>
                                    <label class="text-[10px] font-bold text-slate-500">Hasta</label>
                                    <input type="date" name="fecha_fin" class="w-full text-xs rounded border-slate-300">
                                </div>
                            </div>
                            <div>
                                <label class="text-[10px] font-bold text-slate-500">Monto Mínimo Compra ($)</label>
                                <input type="number" name="monto_minimo" placeholder="Ej: 1000" class="w-full text-xs rounded border-slate-300">
                            </div>
                            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2 rounded text-xs font-bold flex items-center justify-center gap-2">
                                <span class="material-symbols-outlined text-base">picture_as_pdf</span> Exportar Clientes
                            </button>
                        </form>
                    </div>
                    {{-- Gráfico --}}
                    <div class="lg:col-span-2 h-64 relative">
                         @if($clientes->isEmpty()) <p class="text-center text-slate-400 mt-10">Sin clientes destacados.</p> @else <canvas id="chartClientes"></canvas> @endif
                    </div>
                </div>
            </section>

            {{-- FN.19: INVENTARIO --}}
            <section>
                <h3 class="text-lg font-bold text-slate-800 border-l-4 border-amber-500 pl-3 mb-4">FN.19 Movimiento de Inventario</h3>
                <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200 grid grid-cols-1 lg:grid-cols-3 gap-8">
                    {{-- Formulario --}}
                    <div class="lg:col-span-1 border-r border-slate-100 pr-4">
                        <form action="{{ route('reportes.inventario') }}" method="GET" class="space-y-3">
                            @if($isSA) <input type="hidden" name="empresa_id" value="{{ $empresaIdSeleccionada }}"> @endif
                            <div>
                                <label class="text-[10px] font-bold text-slate-500">Categoría</label>
                                <select name="categoria_id" class="w-full text-xs rounded border-slate-300">
                                    <option value="">Todas</option>
                                    @foreach($categorias as $cat)
                                        <option value="{{ $cat->id }}">{{ $cat->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="text-[10px] font-bold text-slate-500">Stock Crítico (Menor a:)</label>
                                <input type="number" name="stock_bajo" placeholder="Ej: 5" class="w-full text-xs rounded border-slate-300">
                            </div>
                            <button type="submit" class="w-full bg-amber-600 hover:bg-amber-700 text-white py-2 rounded text-xs font-bold flex items-center justify-center gap-2">
                                <span class="material-symbols-outlined text-base">picture_as_pdf</span> Exportar Inventario
                            </button>
                        </form>
                    </div>
                    {{-- Gráfico --}}
                    <div class="lg:col-span-2 h-64 relative">
                        @if($inventario->isEmpty()) <p class="text-center text-slate-400 mt-10">Sin inventario.</p> @else <canvas id="chartInventario"></canvas> @endif
                    </div>
                </div>
            </section>

            {{-- FN.20: PROVEEDORES --}}
            <section>
                <h3 class="text-lg font-bold text-slate-800 border-l-4 border-rose-500 pl-3 mb-4">FN.20 Compras a Proveedores</h3>
                <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200 grid grid-cols-1 lg:grid-cols-3 gap-8">
                    {{-- Formulario --}}
                    <div class="lg:col-span-1 border-r border-slate-100 pr-4">
                        <form action="{{ route('reportes.proveedores') }}" method="GET" class="space-y-3">
                            @if($isSA) <input type="hidden" name="empresa_id" value="{{ $empresaIdSeleccionada }}"> @endif
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="text-[10px] font-bold text-slate-500">Desde</label>
                                    <input type="date" name="fecha_inicio" class="w-full text-xs rounded border-slate-300">
                                </div>
                                <div>
                                    <label class="text-[10px] font-bold text-slate-500">Hasta</label>
                                    <input type="date" name="fecha_fin" class="w-full text-xs rounded border-slate-300">
                                </div>
                            </div>
                            <div>
                                <label class="text-[10px] font-bold text-slate-500">Proveedor</label>
                                <select name="proveedor_id" class="w-full text-xs rounded border-slate-300">
                                    <option value="">Todos</option>
                                    @foreach($listaProveedores as $prov)
                                        <option value="{{ $prov->id }}">{{ $prov->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <button type="submit" class="w-full bg-rose-600 hover:bg-rose-700 text-white py-2 rounded text-xs font-bold flex items-center justify-center gap-2">
                                <span class="material-symbols-outlined text-base">picture_as_pdf</span> Exportar Proveedores
                            </button>
                        </form>
                    </div>
                    {{-- Gráfico --}}
                    <div class="lg:col-span-2 h-64 relative">
                        @if($proveedores->isEmpty()) <p class="text-center text-slate-400 mt-10">Sin compras registradas.</p> @else <canvas id="chartProveedores"></canvas> @endif
                    </div>
                </div>
            </section>

        @else
            <div class="flex flex-col items-center justify-center py-20 bg-slate-50 border border-slate-200 border-dashed rounded-xl">
                 <span class="material-symbols-outlined text-6xl text-slate-300 mb-4">analytics</span>
                 <p class="text-slate-500">Selecciona una empresa o registra actividad para ver los reportes.</p>
            </div>
        @endif
    </div>

    {{-- SCRIPTS (GRÁFICOS) --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            @if($isSA && !empty($saasMetrics))
                new Chart(document.getElementById('chartSaasEmpresas'), {
                    type: 'doughnut',
                    data: { labels: {!! json_encode($saasMetrics['empresas_labels']) !!}, datasets: [{ data: {!! json_encode($saasMetrics['empresas_data']) !!}, backgroundColor: ['#10b981', '#64748b'] }] },
                    options: { responsive: true, maintainAspectRatio: false }
                });
                new Chart(document.getElementById('chartSaasSubs'), {
                    type: 'pie',
                    data: { labels: {!! json_encode($saasMetrics['subs_labels']) !!}, datasets: [{ data: {!! json_encode($saasMetrics['subs_data']) !!}, backgroundColor: ['#3b82f6', '#f59e0b', '#ef4444'] }] },
                    options: { responsive: true, maintainAspectRatio: false }
                });
            @endif

            @if($mostrarDatos)
                @if(!$rentabilidad->isEmpty())
                new Chart(document.getElementById('chartRentabilidad'), {
                    type: 'bar',
                    data: { labels: {!! json_encode($rentabilidad->pluck('nombre')) !!}, datasets: [{ label: 'Ganancia Neta ($)', data: {!! json_encode($rentabilidad->pluck('ganancia')) !!}, backgroundColor: '#10b981' }] },
                    options: { responsive: true, maintainAspectRatio: false }
                });
                @endif
                @if(!$clientes->isEmpty())
                new Chart(document.getElementById('chartClientes'), {
                    type: 'doughnut',
                    data: { labels: {!! json_encode($clientes->pluck('nombre')) !!}, datasets: [{ data: {!! json_encode($clientes->pluck('total')) !!}, backgroundColor: ['#3b82f6', '#6366f1', '#8b5cf6'] }] },
                    options: { responsive: true, maintainAspectRatio: false }
                });
                @endif
                @if(!$inventario->isEmpty())
                new Chart(document.getElementById('chartInventario'), {
                    type: 'polarArea',
                    data: { labels: {!! json_encode($inventario->pluck('nombre_categoria')) !!}, datasets: [{ label:'Valor ($)', data: {!! json_encode($inventario->pluck('valor')) !!}, backgroundColor: ['#fcd34d', '#f59e0b', '#d97706'] }] },
                    options: { responsive: true, maintainAspectRatio: false }
                });
                @endif
                @if(!$proveedores->isEmpty())
                new Chart(document.getElementById('chartProveedores'), {
                    type: 'bar',
                    data: { labels: {!! json_encode($proveedores->pluck('nombre')) !!}, datasets: [{ label: 'Gasto ($)', data: {!! json_encode($proveedores->pluck('total')) !!}, backgroundColor: '#f43f5e', indexAxis: 'y' }] },
                    options: { responsive: true, maintainAspectRatio: false }
                });
                @endif
            @endif
        });
    </script>
</x-app-layout>