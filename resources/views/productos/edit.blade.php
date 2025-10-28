<x-app-layout>
  <x-slot name="header">
    <div class="flex items-center justify-between">
      <h1 class="text-xl sm:text-2xl font-semibold text-gray-900 flex items-center gap-2">
        <span class="material-symbols-outlined mi">edit</span>
        Editar producto
      </h1>
      <a href="{{ route('productos.index') }}"
         class="inline-flex items-center gap-2 h-10 px-4 rounded-xl border border-gray-300 text-gray-700 text-sm hover:bg-gray-50">
        <span class="material-symbols-outlined mi text-base">arrow_back</span>
        Volver
      </a>
    </div>
  </x-slot>

  <div class="max-w-5xl mx-auto">
    <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-8">
      @if (session('error'))
        <div class="rounded-lg bg-red-50 text-red-800 ring-1 ring-red-200 p-3">{{ session('error') }}</div>
      @endif
      @if ($errors->any())
        <div class="rounded-lg bg-red-50 text-red-800 ring-1 ring-red-200 p-3">{{ $errors->first() }}</div>
      @endif

      {{-- Form principal --}}
      <form method="POST" action="{{ route('productos.update', $producto) }}" class="space-y-6" enctype="multipart/form-data">
        @csrf @method('PUT')

        @if(isset($empresas) && $empresas->count())
          <div>
            <label class="block text-sm text-gray-700 mb-1">Empresa <span class="text-red-600">*</span></label>
            <select name="id_empresa" class="w-full h-10 rounded-lg border-gray-300 focus:ring-indigo-500 text-sm" required>
              @foreach($empresas as $em)
                <option value="{{ $em->id }}" @selected(old('id_empresa', $producto->id_empresa)==$em->id)>{{ $em->razon_social }}</option>
              @endforeach
            </select>
            @error('id_empresa') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
          </div>
        @endif

        <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
          <div>
            <label class="block text-sm text-gray-700 mb-1">Nombre <span class="text-red-600">*</span></label>
            <input type="text" name="nombre" value="{{ old('nombre', $producto->nombre) }}" class="w-full h-10 rounded-lg border-gray-300 focus:ring-indigo-500 text-sm" required>
            @error('nombre') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
          </div>
          <div>
            <label class="block text-sm text-gray-700 mb-1">SKU</label>
            <input type="text" name="sku" value="{{ old('sku', $producto->sku) }}" class="w-full h-10 rounded-lg border-gray-300 focus:ring-indigo-500 text-sm">
            @error('sku') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
          </div>
          <div>
            <label class="block text-sm text-gray-700 mb-1">Categoría</label>
            <select name="categoria_id" class="w-full h-10 rounded-lg border-gray-300 focus:ring-indigo-500 text-sm">
              <option value="">—</option>
              @foreach($categorias as $cat)
                <option value="{{ $cat->id }}" @selected(old('categoria_id', $producto->categoria_id)==$cat->id)>{{ $cat->nombre }}</option>
              @endforeach
            </select>
            @error('categoria_id') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
          </div>
          <div>
            <label class="block text-sm text-gray-700 mb-1">Precio <span class="text-red-600">*</span></label>
            <input type="number" step="0.01" min="0" name="precio" value="{{ old('precio', $producto->precio) }}" class="w-full h-10 rounded-lg border-gray-300 focus:ring-indigo-500 text-sm" required>
            @error('precio') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
          </div>
          <div>
            <label class="block text-sm text-gray-700 mb-1">Moneda de venta</label>
            <input type="text" name="moneda_venta" value="{{ old('moneda_venta', $producto->moneda_venta) }}" class="w-full h-10 rounded-lg border-gray-300 focus:ring-indigo-500 text-sm">
            @error('moneda_venta') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
          </div>
          <div>
            <label class="block text-sm text-gray-700 mb-1">Stock <span class="text-red-600">*</span></label>
            <input type="number" min="0" name="stock" value="{{ old('stock', $producto->stock) }}" class="w-full h-10 rounded-lg border-gray-300 focus:ring-indigo-500 text-sm" required>
            @error('stock') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
          </div>
        </section>

        <section class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div class="sm:col-span-2">
            <label class="block text-sm text-gray-700 mb-1">Descripción</label>
            <textarea name="descripcion" rows="4" class="w-full rounded-lg border-gray-300 focus:ring-indigo-500 text-sm">{{ old('descripcion', $producto->descripcion) }}</textarea>
            @error('descripcion') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
          </div>
          <div>
            <label class="block text-sm text-gray-700 mb-1">Imagen</label>
            <input type="file" name="imagen" accept="image/*" class="block w-full text-sm text-gray-700 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
            @if($producto->imagen_path)
              <p class="text-xs text-gray-500 mt-1">Imagen actual: <span class="font-mono">{{ $producto->imagen_path }}</span></p>
            @endif
            <p class="text-xs text-gray-500 mt-1">Directorio: <code>storage/app/public/productos</code>.</p>
            @error('imagen') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
          </div>
          <div class="flex items-center gap-2">
            <input type="checkbox" name="activo" value="1" id="activo"
                   class="h-4 w-4 text-indigo-600 border-gray-300 rounded" @checked(old('activo', $producto->activo))>
            <label for="activo" class="text-sm text-gray-700">Activo</label>
          </div>
        </section>

        <div class="flex items-center justify-end gap-2">
          <a href="{{ route('productos.index') }}" class="h-10 px-4 rounded-xl border text-sm">Cancelar</a>
          <button type="submit" class="h-10 px-4 rounded-xl bg-indigo-600 text-white text-sm font-medium">Actualizar</button>
        </div>
      </form>

      {{-- ===== Costos por proveedor (pivote) ===== --}}
      <div class="pt-6 border-t border-gray-200">
        <h2 class="text-sm font-semibold text-gray-800 mb-3 flex items-center gap-2">
          <span class="material-symbols-outlined mi text-base">local_shipping</span>
          Proveedores del producto
        </h2>

        {{-- Agregar proveedor al producto --}}
        <form method="POST" action="{{ route('productos.proveedores.store', $producto) }}"
              class="bg-gray-50 border border-gray-200 rounded-lg p-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">
          @csrf
          <div class="lg:col-span-2">
            <label class="block text-xs text-gray-600 mb-1">Proveedor <span class="text-red-600">*</span></label>
            <select name="proveedor_id" class="w-full h-10 rounded-lg border-gray-300 focus:ring-indigo-500 text-sm" required>
              @php
                $proveedoresDisponibles = \App\Models\Proveedor::query()
                  ->where('id_empresa', $producto->id_empresa)->orderBy('nombre')->get();
              @endphp
              <option value="">Selecciona proveedor…</option>
              @foreach($proveedoresDisponibles as $prov)
                <option value="{{ $prov->id }}">{{ $prov->nombre }}</option>
              @endforeach
            </select>
          </div>
          <div>
            <label class="block text-xs text-gray-600 mb-1">SKU proveedor</label>
            <input type="text" name="sku_proveedor" class="w-full h-10 rounded-lg border-gray-300 focus:ring-indigo-500 text-sm">
          </div>
          <div>
            <label class="block text-xs text-gray-600 mb-1">Costo <span class="text-red-600">*</span></label>
            <input type="number" step="0.01" min="0" name="costo" class="w-full h-10 rounded-lg border-gray-300 focus:ring-indigo-500 text-sm" required>
          </div>
          <div>
            <label class="block text-xs text-gray-600 mb-1">Moneda</label>
            <input type="text" name="moneda" value="MXN" class="w-full h-10 rounded-lg border-gray-300 focus:ring-indigo-500 text-sm">
          </div>
          <div>
            <label class="block text-xs text-gray-600 mb-1">Lead time (días)</label>
            <input type="number" min="0" name="lead_time_dias" value="0" class="w-full h-10 rounded-lg border-gray-300 focus:ring-indigo-500 text-sm">
          </div>
          <div class="flex items-end">
            <div class="flex items-center gap-3">
              <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                <input type="checkbox" name="preferido" value="1" class="h-4 w-4 text-indigo-600 rounded">
                Preferido
              </label>
              <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                <input type="checkbox" name="activo" value="1" class="h-4 w-4 text-indigo-600 rounded" checked>
                Activo
              </label>
            </div>
          </div>
          <div class="lg:col-span-6 flex items-center justify-end">
            <button class="h-10 px-4 rounded-lg bg-indigo-600 text-white text-sm">Agregar proveedor</button>
          </div>
        </form>

        {{-- Tabla de costos --}}
        <div class="mt-4 bg-white rounded-xl border border-gray-200 overflow-hidden">
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
                  <th class="px-4 py-3 text-right">Acciones</th>
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
                    <td class="px-4 py-3">
                      <div class="flex justify-end gap-1">
                        {{-- Edit inline simple: abre modal en iteraciones futuras; aquí form rápido --}}
                        <form method="POST" action="{{ route('productos.proveedores.update', [$producto, $prov]) }}" class="inline">
                          @csrf @method('PUT')
                          <input type="hidden" name="costo" value="{{ $prov->pivot->costo }}">
                          <input type="hidden" name="moneda" value="{{ $prov->pivot->moneda }}">
                          <input type="hidden" name="sku_proveedor" value="{{ $prov->pivot->sku_proveedor }}">
                          <input type="hidden" name="lead_time_dias" value="{{ $prov->pivot->lead_time_dias }}">
                          <input type="hidden" name="moq" value="{{ $prov->pivot->moq }}">
                          <input type="hidden" name="preferido" value="{{ $prov->pivot->preferido ? 1 : 0 }}">
                          <input type="hidden" name="activo" value="{{ $prov->pivot->activo ? 1 : 0 }}">
                          <button type="submit" class="px-2.5 py-1.5 rounded-lg text-gray-700 hover:bg-gray-100" title="Reaplicar">
                            <span class="material-symbols-outlined mi text-base">save</span>
                          </button>
                        </form>
                        <form method="POST" action="{{ route('productos.proveedores.destroy', [$producto, $prov]) }}"
                              onsubmit="return confirm('¿Quitar proveedor del producto?');" class="inline">
                          @csrf @method('DELETE')
                          <button type="submit" class="px-2.5 py-1.5 rounded-lg text-red-700 hover:bg-red-50" title="Quitar">
                            <span class="material-symbols-outlined mi text-base">delete</span>
                          </button>
                        </form>
                      </div>
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="9" class="px-4 py-8 text-center text-gray-500">Este producto aún no tiene proveedores vinculados.</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>

      </div>
    </div>
  </div>
</x-app-layout>
