<x-app-layout>
  <x-slot name="header">
    <div class="flex items-center justify-between">
      <h1 class="text-xl sm:text-2xl font-semibold text-gray-900 flex items-center gap-2">
        <span class="material-symbols-outlined mi">inventory_2</span>
        {{ $producto->nombre }}
      </h1>
      <div class="flex items-center gap-2">
        <a href="{{ route('productos.edit', $producto) }}"
           class="inline-flex items-center gap-2 h-10 px-4 rounded-xl border border-gray-300 text-gray-700 text-sm hover:bg-gray-50">
          <span class="material-symbols-outlined mi text-base">edit</span>
          Editar
        </a>
        <a href="{{ route('productos.index') }}"
           class="inline-flex items-center gap-2 h-10 px-4 rounded-xl border border-gray-300 text-gray-700 text-sm hover:bg-gray-50">
          <span class="material-symbols-outlined mi text-base">arrow_back</span>
          Volver
        </a>
      </div>
    </div>
  </x-slot>

  <div class="max-w-6xl mx-auto space-y-6">
    @if (session('success'))
      <div class="rounded-lg bg-green-50 text-green-800 ring-1 ring-green-200 p-3">{{ session('success') }}</div>
    @endif
    @if (session('error'))
      <div class="rounded-lg bg-red-50 text-red-800 ring-1 ring-red-200 p-3">{{ session('error') }}</div>
    @endif

    <div class="bg-white rounded-xl border border-gray-200 p-6">
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
          <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
            <div>
              <dt class="text-gray-500">SKU</dt>
              <dd class="text-gray-900 font-medium">{{ $producto->sku ?: '—' }}</dd>
            </div>
            <div>
              <dt class="text-gray-500">Categoría</dt>
              <dd class="text-gray-900">{{ $producto->categoria?->nombre ?: '—' }}</dd>
            </div>
            <div>
              <dt class="text-gray-500">Precio</dt>
              <dd class="text-gray-900 font-medium">{{ number_format($producto->precio,2) }} {{ $producto->moneda_venta }}</dd>
            </div>
            <div>
              <dt class="text-gray-500">Stock</dt>
              <dd class="text-gray-900">{{ $producto->stock }}</dd>
            </div>
            <div>
              <dt class="text-gray-500">Estado</dt>
              <dd>
                <span class="inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full
                  {{ $producto->activo ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                  {{ $producto->activo ? 'Activo' : 'Inactivo' }}
                </span>
              </dd>
            </div>
            <div class="sm:col-span-2">
              <dt class="text-gray-500">Descripción</dt>
              <dd class="text-gray-900">{{ $producto->descripcion ?: '—' }}</dd>
            </div>
          </dl>
        </div>
        <div>
          @if($producto->imagen_path)
            <img src="{{ asset('storage/'.$producto->imagen_path) }}" alt="{{ $producto->nombre }}" class="w-full h-48 object-cover rounded-lg border">
          @else
            <div class="h-48 rounded-lg border flex items-center justify-center text-gray-400">
              Sin imagen
            </div>
          @endif
        </div>
      </div>
    </div>

    {{-- Proveedores del producto --}}
    <div class="bg-white rounded-xl border border-gray-200 p-6">
      <h2 class="text-sm font-semibold text-gray-800 mb-3 flex items-center gap-2">
        <span class="material-symbols-outlined mi text-base">local_shipping</span>
        Proveedores y costos
      </h2>

      <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead class="bg-gray-50 text-gray-700">
            <tr>
              <th class="px-4 py-3 text-left">Proveedor</th>
              <th class="px-4 py-3 text-left">SKU proveedor</th>
              <th class="px-4 py-3 text-left">Costo</th>
              <th class="px-4 py-3 text-left">Moneda</th>
              <th class="px-4 py-3 text-left">Lead time</th>
              <th class="px-4 py-3 text-left">MOQ</th>
              <th class="px-4 py-3 text-left">Preferido</th>
              <th class="px-4 py-3 text-left">Activo</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            @php $producto->loadMissing('proveedores'); @endphp
            @forelse ($producto->proveedores as $prov)
              <tr class="hover:bg-gray-50">
                <td class="px-4 py-3 text-gray-900 font-medium">{{ $prov->nombre }}</td>
                <td class="px-4 py-3 text-gray-700">{{ $prov->pivot->sku_proveedor ?: '—' }}</td>
                <td class="px-4 py-3 text-gray-700">{{ number_format($prov->pivot->costo, 2) }}</td>
                <td class="px-4 py-3 text-gray-700">{{ $prov->pivot->moneda }}</td>
                <td class="px-4 py-3 text-gray-700">{{ $prov->pivot->lead_time_dias }} días</td>
                <td class="px-4 py-3 text-gray-700">{{ $prov->pivot->moq }}</td>
                <td class="px-4 py-3">
                  <span class="inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full
                    {{ $prov->pivot->preferido ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-600' }}">
                    {{ $prov->pivot->preferido ? 'Sí' : 'No' }}
                  </span>
                </td>
                <td class="px-4 py-3">
                  <span class="inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full
                    {{ $prov->pivot->activo ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                    {{ $prov->pivot->activo ? 'Activo' : 'Inactivo' }}
                  </span>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="8" class="px-4 py-8 text-center text-gray-500">No hay proveedores vinculados.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</x-app-layout>
