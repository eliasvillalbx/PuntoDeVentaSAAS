{{-- resources/views/productos/index.blade.php --}}
<x-app-layout>
  <x-slot name="header">
    <div class="flex items-center justify-between">
      <h1 class="text-xl sm:text-2xl font-semibold text-gray-900 flex items-center gap-2">
        <span class="material-symbols-outlined mi">inventory_2</span>
        Productos
      </h1>
      <a href="{{ route('productos.create') }}"
         class="inline-flex items-center gap-2 h-10 px-4 rounded-xl bg-indigo-600 text-white text-sm font-medium shadow hover:bg-indigo-700">
        <span class="material-symbols-outlined mi text-base">add</span>
        Nuevo producto
      </a>
    </div>
  </x-slot>

  <div class="max-w-7xl mx-auto space-y-6">
    {{-- Filtros --}}
    <form method="GET" action="{{ route('productos.index') }}"
          class="bg-white rounded-xl border border-gray-200 p-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">
      <div class="lg:col-span-2">
        <label class="block text-xs text-gray-600 mb-1">Buscar</label>
        <input type="text" name="q" value="{{ $q ?? '' }}" placeholder="Nombre, SKU o descripción…"
               class="w-full h-10 rounded-lg border-gray-300 focus:ring-indigo-500 text-sm">
      </div>

      @if(isset($empresas) && $empresas->count())
        <div class="lg:col-span-2">
          <label class="block text-xs text-gray-600 mb-1">Empresa</label>
          <select name="empresa_id" class="w-full h-10 rounded-lg border-gray-300 focus:ring-indigo-500 text-sm">
            <option value="">Todas</option>
            @foreach ($empresas as $em)
              <option value="{{ $em->id }}" @selected(($empresaId ?? null) == $em->id)>{{ $em->razon_social }}</option>
            @endforeach
          </select>
        </div>
      @endif

      <div>
        <label class="block text-xs text-gray-600 mb-1">Categoría</label>
        <select name="categoria_id" class="w-full h-10 rounded-lg border-gray-300 focus:ring-indigo-500 text-sm">
          <option value="">Todas</option>
          @foreach ($categorias as $cat)
            <option value="{{ $cat->id }}" @selected(($categoriaId ?? null) == $cat->id)>{{ $cat->nombre }}</option>
          @endforeach
        </select>
      </div>

      <div>
        <label class="block text-xs text-gray-600 mb-1">Activo</label>
        <select name="activo" class="w-full h-10 rounded-lg border-gray-300 focus:ring-indigo-500 text-sm">
          <option value="">Todos</option>
          <option value="1" @selected(($activo ?? '')==='1')>Sí</option>
          <option value="0" @selected(($activo ?? '')==='0')>No</option>
        </select>
      </div>

      <div class="lg:col-span-6 flex items-end justify-end gap-2">
        <button type="submit" class="h-10 px-4 rounded-lg bg-gray-900 text-white text-sm">Aplicar</button>
        <a href="{{ route('productos.index') }}" class="h-10 px-4 rounded-lg border text-sm text-gray-700">Limpiar</a>
      </div>
    </form>

    {{-- Tabla --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead class="bg-gray-50 text-gray-700">
            <tr>
              <th class="px-4 py-3 text-left">Producto</th>
              <th class="px-4 py-3 text-left">SKU</th>
              <th class="px-4 py-3 text-left">Categoría</th>
              <th class="px-4 py-3 text-left">Precio</th>
              <th class="px-4 py-3 text-left">Stock</th>
              <th class="px-4 py-3 text-left">Estado</th>
              <th class="px-4 py-3 text-right">Acciones</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            @forelse ($productos as $p)
              <tr class="hover:bg-gray-50">
                <td class="px-4 py-3 text-gray-900 font-medium">
                  <a href="{{ route('productos.show', $p) }}" class="hover:underline">{{ $p->nombre }}</a>
                </td>
                <td class="px-4 py-3 text-gray-700">{{ $p->sku ?: '—' }}</td>
                <td class="px-4 py-3 text-gray-700">{{ $p->categoria?->nombre ?: '—' }}</td>
                <td class="px-4 py-3 text-gray-700">{{ number_format($p->precio, 2) }} {{ $p->moneda_venta }}</td>
                <td class="px-4 py-3 text-gray-700">{{ $p->stock }}</td>
                <td class="px-4 py-3">
                  <span class="inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full
                    {{ $p->activo ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                    <span class="material-symbols-outlined mi text-sm">{{ $p->activo ? 'check' : 'block' }}</span>
                    {{ $p->activo ? 'Activo' : 'Inactivo' }}
                  </span>
                </td>
                <td class="px-4 py-3">
                  <div class="flex justify-end gap-1">
                    {{-- Ver --}}
                    <a href="{{ route('productos.show', $p) }}"
                       class="px-2.5 py-1.5 rounded-lg text-gray-700 hover:bg-gray-100" title="Ver">
                      <span class="material-symbols-outlined mi text-base">visibility</span>
                    </a>
                    {{-- Editar --}}
                    <a href="{{ route('productos.edit', $p) }}"
                       class="px-2.5 py-1.5 rounded-lg text-gray-700 hover:bg-gray-100" title="Editar">
                      <span class="material-symbols-outlined mi text-base">edit</span>
                    </a>
                    {{-- Eliminar --}}
                    <form method="POST" action="{{ route('productos.destroy', $p) }}"
                          onsubmit="return confirm('¿Eliminar producto?');">
                      @csrf @method('DELETE')
                      <button type="submit" class="px-2.5 py-1.5 rounded-lg text-red-700 hover:bg-red-50" title="Eliminar">
                        <span class="material-symbols-outlined mi text-base">delete</span>
                      </button>
                    </form>
                  </div>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="7" class="px-4 py-8 text-center text-gray-500">No hay productos registrados.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div class="px-4 py-3 border-t border-gray-100 bg-white">
        {{ $productos->onEachSide(1)->links() }}
      </div>
    </div>
  </div>
</x-app-layout>
