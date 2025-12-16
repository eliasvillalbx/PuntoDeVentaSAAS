<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Reporte General de Suscripciones SaaS') }}
        </h2>
    </x-slot>

    {{-- Script de Chart.js --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- 1. FILTROS Y EXPORTACIÓN --}}
            <div class="bg-white p-4 shadow sm:rounded-lg">
                <form method="GET" action="{{ route('dashboard') }}" class="flex flex-col md:flex-row gap-4 items-end justify-between">
                    <div class="flex gap-4 flex-wrap w-full md:w-auto">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Estado</label>
                            <select name="estado" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="">Todos</option>
                                <option value="activa" {{ request('estado') == 'activa' ? 'selected' : '' }}>Activa</option>
                                <option value="vencida" {{ request('estado') == 'vencida' ? 'selected' : '' }}>Vencida</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Plan</label>
                            <select name="plan" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="">Todos</option>
                                <option value="mensual" {{ request('plan') == 'mensual' ? 'selected' : '' }}>Mensual</option>
                                <option value="trimestral" {{ request('plan') == 'trimestral' ? 'selected' : '' }}>Trimestral</option>
                                <option value="anual" {{ request('plan') == 'anual' ? 'selected' : '' }}>Anual</option>
                            </select>
                        </div>
                        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 mt-auto h-10">
                            Filtrar
                        </button>
                        <a href="{{ route('dashboard') }}" class="text-gray-500 underline mt-auto mb-2 text-sm">Limpiar</a>
                    </div>

                    {{-- Botones de Exportar --}}
                    <div class="flex gap-2">
                        <a href="{{ route('export.excel', request()->all()) }}" class="flex items-center gap-2 bg-green-600 text-white px-3 py-2 rounded-md hover:bg-green-700 text-sm">
                            <span>XLS</span>
                        </a>
                        <a href="{{ route('export.pdf', request()->all()) }}" class="flex items-center gap-2 bg-red-600 text-white px-3 py-2 rounded-md hover:bg-red-700 text-sm">
                             <span>PDF</span>
                        </a>
                    </div>
                </form>
            </div>

            {{-- 2. TARJETAS DE KPIs --}}
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-l-4 border-blue-500">
                    <div class="text-gray-500 text-sm font-medium">Empresas Totales</div>
                    <div class="text-3xl font-bold text-gray-800">{{ $totalEmpresas }}</div>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-l-4 border-green-500">
                    <div class="text-gray-500 text-sm font-medium">Suscripciones Activas</div>
                    <div class="text-3xl font-bold text-green-700">{{ $activas }}</div>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-l-4 border-red-500">
                    <div class="text-gray-500 text-sm font-medium">Vencidas / Inactivas</div>
                    <div class="text-3xl font-bold text-red-700">{{ $inactivas }}</div>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-l-4 border-indigo-500">
                    <div class="text-gray-500 text-sm font-medium">MRR Estimado (Mensual)</div>
                    <div class="text-3xl font-bold text-indigo-700">${{ number_format($mrr, 2) }}</div>
                </div>
            </div>

            {{-- 3. GRÁFICOS (Chart.js) --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                    <h3 class="text-lg font-bold text-gray-700 mb-4">Distribución por Planes</h3>
                    <div class="relative h-64 w-full">
                        <canvas id="chartPlanes"></canvas>
                    </div>
                </div>
                <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                    <h3 class="text-lg font-bold text-gray-700 mb-4">Estado de Suscripciones</h3>
                    <div class="relative h-64 w-full">
                        <canvas id="chartEstados"></canvas>
                    </div>
                </div>
            </div>

            {{-- 4. TABLA DETALLADA --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Listado Detallado</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Empresa</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Plan</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Inicio</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vencimiento</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Renovado</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($suscripciones as $sub)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">{{ $sub->empresa->nombre ?? 'N/A' }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            {{ $sub->plan == 'anual' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800' }}">
                                            {{ ucfirst($sub->plan) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $sub->fecha_inicio?->format('d/m/Y') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $sub->fecha_vencimiento?->format('d/m/Y') }}
                                        @if($sub->fecha_vencimiento?->isPast() && $sub->estado == 'activa')
                                            <span class="text-red-500 text-xs">(Vence hoy/Pasado)</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            {{ $sub->estado == 'activa' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                            {{ ucfirst($sub->estado) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $sub->renovado ? 'Sí' : 'No' }}
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-4 text-center text-gray-500">No se encontraron suscripciones con los filtros seleccionados.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- SCRIPTS PARA GRÁFICOS --}}
    <script>
        // Datos venidos de Laravel (Blade)
        const planesLabels = {!! json_encode($planesData->keys()) !!};
        const planesValues = {!! json_encode($planesData->values()) !!};
        
        const estadosLabels = {!! json_encode($estadosData->keys()) !!};
        const estadosValues = {!! json_encode($estadosData->values()) !!};

        // 1. Gráfico de Planes (Dona)
        const ctxPlanes = document.getElementById('chartPlanes').getContext('2d');
        new Chart(ctxPlanes, {
            type: 'doughnut',
            data: {
                labels: planesLabels,
                datasets: [{
                    data: planesValues,
                    backgroundColor: ['#6366f1', '#10b981', '#f59e0b'], // Indigo, Green, Amber
                }]
            },
            options: { responsive: true, maintainAspectRatio: false }
        });

        // 2. Gráfico de Estados (Barra)
        const ctxEstados = document.getElementById('chartEstados').getContext('2d');
        new Chart(ctxEstados, {
            type: 'bar',
            data: {
                labels: estadosLabels,
                datasets: [{
                    label: 'Cantidad',
                    data: estadosValues,
                    backgroundColor: ['#10b981', '#ef4444'], // Green, Red
                }]
            },
            options: { 
                responsive: true, 
                maintainAspectRatio: false,
                scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
            }
        });
    </script>
</x-app-layout>