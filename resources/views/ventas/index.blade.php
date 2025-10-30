<x-app-layout>
  <x-slot name="header">
    <div class="flex items-center justify-between">
      <h1 class="text-xl sm:text-2xl font-semibold text-gray-900 flex items-center gap-2">
        <span class="material-symbols-outlined mi">point_of_sale</span>
        Ventas & Prefacturas
      </h1>
      <div class="flex items-center gap-2">
        <a href="{{ route('ventas.create', ['empresa_id' => $empresaId]) }}"
           class="inline-flex items-center gap-2 h-10 px-4 rounded-xl bg-indigo-600 text-white text-sm font-medium shadow hover:bg-indigo-700">
          <span class="material-symbols-outlined mi text-base">add_shopping_cart</span>
          Nueva
        </a>
      </div>
    </div>
  </x-slot>

  <div class="max-w-7xl mx-auto space-y-6">
    {{-- Alertas --}}
    @if (session('status'))
      <div class="rounded-lg bg-green-50 text-green-800 ring-1 ring-green-200 p-3">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
      <div class="rounded-lg bg-red-50 text-red-800 ring-1 ring-red-200 p-3">{{ $errors->first() }}</div>
    @endif

    {{-- KPIs --}}
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
      <div class="bg-white rounded-xl border border-gray-200 p-4">
        <div class="text-xs text-gray-500">Ventas hoy</div>
        <div class="text-xl font-semibold mt-1">${{ number_format($totales['hoy'] ?? 0, 2) }}</div>
      </div>
      <div class="bg-white rounded-xl border border-gray-200 p-4">
        <div class="text-xs text-gray-500">Ventas mes</div>
        <div class="text-xl font-semibold mt-1">${{ number_format($totales['mes'] ?? 0, 2) }}</div>
      </div>
      <div class="bg-white rounded-xl border border-gray-200 p-4">
        <div class="text-xs text-gray-500">Total registros</div>
        <div class="text-xl font-semibold mt-1">{{ number_format($totales['conteo'] ?? 0) }}</div>
      </div>
      <div class="bg-white rounded-xl border border-gray-200 p-4">
        <div class="flex items-center justify-between">
          <div>
            <div class="text-xs text-gray-500">Prefacturas pendientes</div>
            <div class="text-xl font-semibold mt-1">{{ number_format($totales['prefPend'] ?? 0) }}</div>
          </div>
          <a href="{{ route('ventas.index', ['empresa_id'=>$empresaId, 'estatus'=>'prefactura']) }}"
             class="text-indigo-600 text-xs hover:underline">Ver</a>
        </div>
      </div>
    </div>

    {{-- Filtros --}}
    <div class="bg-white rounded-xl border border-gray-200 p-4">
      <form method="GET" action="{{ route('ventas.index') }}" class="grid grid-cols-1 md:grid-cols-12 gap-3">
        @if(auth()->user()->hasRole('superadmin'))
          <div class="md:col-span-3">
            <label class="block text-xs text-gray-600 mb-1">Empresa</label>
            <select name="empresa_id" class="w-full h-10 rounded-lg border-gray-300 text-sm">
              @foreach ($empresas as $em)
                <option value="{{ $em->id }}" @selected((int)$empresaId === (int)$em->id)>
                  {{ $em->nombre_comercial ?? $em->razon_social }}
                </option>
              @endforeach
            </select>
          </div>
        @else
          <input type="hidden" name="empresa_id" value="{{ (int)$empresaId }}">
        @endif

        <div class="md:col-span-2">
          <label class="block text-xs text-gray-600 mb-1">Estatus</label>
          <select name="estatus" class="w-full h-10 rounded-lg border-gray-300 text-sm">
            <option value="">Todos</option>
            @foreach (['borrador','prefactura','facturada','cancelada'] as $st)
              <option value="{{ $st }}" @selected(request('estatus')===$st)>{{ ucfirst($st) }}</option>
            @endforeach
          </select>
        </div>

        <div class="md:col-span-2">
          <label class="block text-xs text-gray-600 mb-1">Vendedor</label>
          <select name="vendedor_id" class="w-full h-10 rounded-lg border-gray-300 text-sm">
            <option value="">Todos</option>
            @foreach ($vendedores as $v)
              <option value="{{ $v->id }}" @selected((int)request('vendedor_id')===$v->id)>{{ $v->nombre }} {{ $v->apellido_paterno }}</option>
            @endforeach
          </select>
        </div>

        <div class="md:col-span-2">
          <label class="block text-xs text-gray-600 mb-1">Cliente</label>
          <select name="cliente_id" class="w-full h-10 rounded-lg border-gray-300 text-sm">
            <option value="">Todos</option>
            @foreach ($clientes as $c)
              <option value="{{ $c->id }}" @selected((int)request('cliente_id')===$c->id)>{{ $c->nombre ?? $c->razon_social }}</option>
            @endforeach
          </select>
        </div>

        <div class="md:col-span-1">
          <label class="block text-xs text-gray-600 mb-1">Desde</label>
          <input type="date" name="fecha_inicio" value="{{ request('fecha_inicio') }}" class="w-full h-10 rounded-lg border-gray-300 text-sm">
        </div>
        <div class="md:col-span-1">
          <label class="block text-xs text-gray-600 mb-1">Hasta</label>
          <input type="date" name="fecha_fin" value="{{ request('fecha_fin') }}" class="w-full h-10 rounded-lg border-gray-300 text-sm">
        </div>

        <div class="md:col-span-1">
          <label class="block text-xs text-gray-600 mb-1">Buscar</label>
          <input type="text" name="q" value="{{ request('q') }}" placeholder="Obs/ID/Total" class="w-full h-10 rounded-lg border-gray-300 text-sm">
        </div>

        <div class="md:col-span-12 flex justify-end gap-2">
          <a href="{{ route('ventas.index', ['empresa_id'=>$empresaId]) }}"
             class="inline-flex items-center gap-2 h-10 px-4 rounded-lg border border-gray-300 text-sm text-gray-700 hover:bg-gray-50">
            <span class="material-symbols-outlined mi text-base">close</span> Limpiar
          </a>
          <button class="inline-flex items-center gap-2 h-10 px-4 rounded-lg bg-gray-900 text-white text-sm hover:bg-black">
            <span class="material-symbols-outlined mi text-base">tune</span> Filtrar
          </button>
        </div>
      </form>
    </div>

    {{-- Tabla --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-3 py-2 text-left font-semibold text-gray-700">#</th>
              <th class="px-3 py-2 text-left font-semibold text-gray-700">Fecha</th>
              <th class="px-3 py-2 text-left font-semibold text-gray-700">Vendedor</th>
              <th class="px-3 py-2 text-left font-semibold text-gray-700">Cliente</th>
              <th class="px-3 py-2 text-right font-semibold text-gray-700">Subtotal</th>
              <th class="px-3 py-2 text-right font-semibold text-gray-700">IVA</th>
              <th class="px-3 py-2 text-right font-semibold text-gray-700">Total</th>
              <th class="px-3 py-2 text-center font-semibold text-gray-700">Estatus</th>
              <th class="px-3 py-2 text-right font-semibold text-gray-700">Acciones</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            @forelse ($ventas as $v)
              <tr>
                <td class="px-3 py-2">{{ $v->id }}</td>
                <td class="px-3 py-2">{{ \Illuminate\Support\Carbon::parse($v->fecha_venta)->format('d/m/Y') }}</td>
                <td class="px-3 py-2">{{ $v->usuario?->nombre }} {{ $v->usuario?->apellido_paterno }}</td>
                <td class="px-3 py-2">{{ $v->cliente?->nombre ?? $v->cliente?->razon_social ?? 'Venta directa' }}</td>
                <td class="px-3 py-2 text-right">${{ number_format($v->subtotal, 2) }}</td>
                <td class="px-3 py-2 text-right">${{ number_format($v->iva, 2) }}</td>
                <td class="px-3 py-2 text-right font-medium">${{ number_format($v->total, 2) }}</td>
                <td class="px-3 py-2 text-center">
                  @php
                    $badge = [
                      'borrador'   => 'bg-gray-100 text-gray-700 ring-gray-200',
                      'prefactura' => 'bg-amber-100 text-amber-800 ring-amber-200',
                      'facturada'  => 'bg-green-100 text-green-800 ring-green-200',
                      'cancelada'  => 'bg-red-100 text-red-800 ring-red-200',
                    ][$v->estatus] ?? 'bg-gray-100 text-gray-700 ring-gray-200';
                  @endphp
                  <span class="inline-flex items-center px-2 h-6 rounded-full text-xs font-semibold ring-1 {{ $badge }}">
                    {{ ucfirst($v->estatus) }}
                  </span>
                </td>
                <td class="px-3 py-2">
                  <div class="flex items-center justify-end gap-1">
                    <a href="{{ route('ventas.show', $v) }}"
                       class="inline-flex items-center h-8 px-2 rounded-lg text-gray-700 hover:bg-gray-50">
                      <span class="material-symbols-outlined mi text-base">visibility</span>
                    </a>
                    @if($v->estatus !== 'cancelada')
                      <a href="{{ route('ventas.edit', $v) }}"
                         class="inline-flex items-center h-8 px-2 rounded-lg text-gray-700 hover:bg-gray-50">
                        <span class="material-symbols-outlined mi text-base">edit</span>
                      </a>
                    @endif
                    <form method="POST" action="{{ route('ventas.destroy', $v) }}"
                          onsubmit="return confirm('¿Eliminar venta #{{ $v->id }}?');">
                      @csrf @method('DELETE')
                      <button class="inline-flex items-center h-8 px-2 rounded-lg text-red-700 hover:bg-red-50">
                        <span class="material-symbols-outlined mi text-base">delete</span>
                      </button>
                    </form>
                  </div>
                </td>
              </tr>
            @empty
              <tr><td colspan="9" class="px-3 py-6 text-center text-gray-500">Sin resultados.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>

      {{-- Paginación --}}
      <div class="p-3 border-t border-gray-200">
        {{ $ventas->links() }}
      </div>
    </div>
  </div>
</x-app-layout>
