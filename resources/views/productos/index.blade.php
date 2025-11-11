<x-app-layout>
  <x-slot name="header">
    <div class="flex items-center justify-between">
      <h1 class="text-xl sm:text-2xl font-semibold text-gray-900 flex items-center gap-2">
        <span class="material-symbols-outlined mi">inventory_2</span>
        Productos
      </h1>

      <div class="flex items-center gap-2">
        <a href="{{ route('productos.create') }}"
           class="inline-flex items-center gap-2 h-10 px-4 rounded-xl bg-gray-800 text-white text-sm hover:bg-gray-900">
          <span class="material-symbols-outlined mi text-base">add</span>
          Nuevo producto
        </a>
      </div>
    </div>
  </x-slot>

  <div class="max-w-7xl mx-auto space-y-6">
    @if ($errors->any())
      <div class="rounded-lg bg-red-50 text-red-800 ring-1 ring-red-200 p-3">
        {{ $errors->first() }}
      </div>
    @endif
    @if (session('success'))
      <div class="rounded-lg bg-green-50 text-green-800 ring-1 ring-green-200 p-3">{{ session('success') }}</div>
    @endif

    {{-- Filtros --}}
    <form method="GET" class="bg-white rounded-xl border p-4 grid grid-cols-1 sm:grid-cols-5 gap-3">
      <div class="sm:col-span-2 flex">
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Buscar por nombre o SKU…"
               class="flex-1 rounded-l-lg border-gray-300">
        <button class="px-3 rounded-r-lg bg-gray-800 text-white">Buscar</button>
      </div>

      <select name="categoria_id" class="rounded-lg border-gray-300">
        <option value="">Categoría</option>
        @foreach (($categorias ?? []) as $c)
          <option value="{{ $c->id }}" @selected(request('categoria_id') == $c->id)>{{ $c->nombre }}</option>
        @endforeach
      </select>

      <select name="activo" class="rounded-lg border-gray-300">
        <option value="">Estado</option>
        <option value="1" @selected(request('activo')==='1')>Activo</option>
        <option value="0" @selected(request('activo')==='0')>Inactivo</option>
      </select>

      <div class="flex items-center justify-end">
        <a href="{{ route('productos.index') }}" class="text-sm text-gray-600 hover:underline">Limpiar</a>
      </div>
    </form>

    {{-- Tabla --}}
    <div class="bg-white rounded-xl border overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="bg-gray-50 text-gray-700">
          <tr>
            <th class="px-4 py-3 text-left">SKU</th>
            <th class="px-4 py-3 text-left">Producto</th>
            <th class="px-4 py-3 text-left">Categoría</th>
            <th class="px-4 py-3 text-right">Precio</th>
            <th class="px-4 py-3 text-right">Stock</th>
            <th class="px-4 py-3 text-left">Estado</th>
            <th class="px-4 py-3 text-right">Acciones</th>
          </tr>
        </thead>

        <tbody class="divide-y divide-gray-100">
          @forelse ($productos as $p)
            <tr class="hover:bg-gray-50">
              <td class="px-4 py-3 text-gray-700">{{ $p->sku ?: '—' }}</td>
              <td class="px-4 py-3 font-medium text-gray-900">
                <a href="{{ route('productos.show', $p) }}" class="hover:underline">{{ $p->nombre }}</a>
              </td>
              <td class="px-4 py-3 text-gray-700">{{ $p->categoria?->nombre ?: '—' }}</td>
              <td class="px-4 py-3 text-right text-gray-700">
                {{ number_format($p->precio, 2) }} {{ $p->moneda_venta ?? 'MXN' }}
              </td>
              <td class="px-4 py-3 text-right text-gray-700">{{ number_format($p->stock, 2) }}</td>
              <td class="px-4 py-3">
                <span class="inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full
                  {{ $p->activo ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                  {{ $p->activo ? 'Activo' : 'Inactivo' }}
                </span>
              </td>
              <td class="px-4 py-3">
                <div class="flex justify-end gap-2">
                  {{-- Ver --}}
                  <a href="{{ route('productos.show', $p) }}"
                     class="inline-flex items-center gap-1 text-cyan-700 hover:underline">
                    <span class="material-symbols-outlined mi text-base">visibility</span> Ver
                  </a>

                  {{-- Editar --}}
                  <a href="{{ route('productos.edit', $p) }}"
                     class="inline-flex items-center gap-1 text-gray-700 hover:underline">
                    <span class="material-symbols-outlined mi text-base">edit</span> Editar
                  </a>

                  {{-- Abastecer / Reabastecer --}}
                  <a href="{{ route('compras.create', ['producto_id' => $p->id, 'cantidad' => 1]) }}"
                     class="inline-flex items-center gap-1 text-white bg-cyan-600 hover:bg-cyan-700 rounded-lg px-3 py-1.5"
                     title="Crear compra con este producto">
                    <span class="material-symbols-outlined mi text-base">inventory_2</span>
                    Abastecer
                  </a>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="px-4 py-10">
                <div class="text-center text-gray-500">
                  No hay productos para mostrar.
                  <div class="mt-3">
                    <a href="{{ route('productos.create') }}"
                       class="inline-flex items-center gap-2 h-10 px-4 rounded-xl bg-gray-800 text-white text-sm hover:bg-gray-900">
                      <span class="material-symbols-outlined mi text-base">add</span>
                      Crear producto
                    </a>
                    <a href="{{ route('compras.create') }}"
                       class="inline-flex items-center gap-2 h-10 px-4 rounded-xl bg-cyan-600 text-white text-sm hover:bg-cyan-700 ml-2">
                      <span class="material-symbols-outlined mi text-base">shopping_cart</span>
                      Crear compra
                    </a>
                  </div>
                </div>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div>
      {{ $productos->links() }}
    </div>
  </div>
</x-app-layout>
