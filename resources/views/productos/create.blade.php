<x-app-layout>
  <x-slot name="header">
    <div class="flex items-center justify-between">
      <h1 class="text-xl sm:text-2xl font-semibold text-gray-900 flex items-center gap-2">
        <span class="material-symbols-outlined mi">add</span>
        Nuevo producto
      </h1>
      <a href="{{ route('productos.index') }}"
         class="inline-flex items-center gap-2 h-10 px-4 rounded-xl border border-gray-300 text-gray-700 text-sm hover:bg-gray-50">
        <span class="material-symbols-outlined mi text-base">arrow_back</span>
        Volver
      </a>
    </div>
  </x-slot>

  <div class="max-w-5xl mx-auto">
    <div class="bg-white rounded-xl border border-gray-200 p-6">
      @if (session('error'))
        <div class="mb-4 rounded-lg bg-red-50 text-red-800 ring-1 ring-red-200 p-3">{{ session('error') }}</div>
      @endif
      @if ($errors->any())
        <div class="mb-4 rounded-lg bg-red-50 text-red-800 ring-1 ring-red-200 p-3">{{ $errors->first() }}</div>
      @endif

      <form method="POST" action="{{ route('productos.store') }}" class="space-y-8" enctype="multipart/form-data">
        @csrf

        @if(isset($empresas) && $empresas->count())
          <div>
            <label class="block text-sm text-gray-700 mb-1">Empresa <span class="text-red-600">*</span></label>
            <select name="id_empresa" class="w-full h-10 rounded-lg border-gray-300 focus:ring-indigo-500 text-sm" required>
              <option value="">Selecciona empresa…</option>
              @foreach($empresas as $em)
                <option value="{{ $em->id }}" @selected(old('id_empresa')==$em->id)>{{ $em->razon_social }}</option>
              @endforeach
            </select>
            @error('id_empresa') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
          </div>
        @endif

        <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
          <div>
            <label class="block text-sm text-gray-700 mb-1">Nombre <span class="text-red-600">*</span></label>
            <input type="text" name="nombre" value="{{ old('nombre') }}" class="w-full h-10 rounded-lg border-gray-300 focus:ring-indigo-500 text-sm" required>
            @error('nombre') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
          </div>
          <div>
            <label class="block text-sm text-gray-700 mb-1">SKU</label>
            <input type="text" name="sku" value="{{ old('sku') }}" class="w-full h-10 rounded-lg border-gray-300 focus:ring-indigo-500 text-sm">
            @error('sku') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
          </div>
          <div>
            <label class="block text-sm text-gray-700 mb-1">Categoría</label>
            <select name="categoria_id" class="w-full h-10 rounded-lg border-gray-300 focus:ring-indigo-500 text-sm">
              <option value="">—</option>
              @foreach($categorias as $cat)
                <option value="{{ $cat->id }}" @selected(old('categoria_id')==$cat->id)>{{ $cat->nombre }}</option>
              @endforeach
            </select>
            @error('categoria_id') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
          </div>
          <div>
            <label class="block text-sm text-gray-700 mb-1">Precio <span class="text-red-600">*</span></label>
            <input type="number" step="0.01" min="0" name="precio" value="{{ old('precio', 0) }}" class="w-full h-10 rounded-lg border-gray-300 focus:ring-indigo-500 text-sm" required>
            @error('precio') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
          </div>
          <div>
            <label class="block text-sm text-gray-700 mb-1">Moneda de venta</label>
            <input type="text" name="moneda_venta" value="{{ old('moneda_venta','MXN') }}" class="w-full h-10 rounded-lg border-gray-300 focus:ring-indigo-500 text-sm">
            @error('moneda_venta') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
          </div>
          <div>
            <label class="block text-sm text-gray-700 mb-1">Stock <span class="text-red-600">*</span></label>
            <input type="number" min="0" name="stock" value="{{ old('stock', 0) }}" class="w-full h-10 rounded-lg border-gray-300 focus:ring-indigo-500 text-sm" required>
            @error('stock') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
          </div>
        </section>

        <section class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div class="sm:col-span-2">
            <label class="block text-sm text-gray-700 mb-1">Descripción</label>
            <textarea name="descripcion" rows="4" class="w-full rounded-lg border-gray-300 focus:ring-indigo-500 text-sm">{{ old('descripcion') }}</textarea>
            @error('descripcion') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
          </div>
          <div>
            <label class="block text-sm text-gray-700 mb-1">Imagen</label>
            <input type="file" name="imagen" accept="image/*" class="block w-full text-sm text-gray-700 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
            <p class="text-xs text-gray-500 mt-1">Se guardará en <code>storage/app/public/productos</code>. Corre <code>php artisan storage:link</code> una vez.</p>
            @error('imagen') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
          </div>
          <div class="flex items-center gap-2">
            <input type="checkbox" name="activo" value="1" id="activo" class="h-4 w-4 text-indigo-600 border-gray-300 rounded" @checked(old('activo', true))>
            <label for="activo" class="text-sm text-gray-700">Activo</label>
          </div>
        </section>

        <div class="flex items-center justify-end gap-2">
          <a href="{{ route('productos.index') }}" class="h-10 px-4 rounded-xl border text-sm">Cancelar</a>
          <button type="submit" class="h-10 px-4 rounded-xl bg-indigo-600 text-white text-sm font-medium">Guardar</button>
        </div>
      </form>
    </div>
  </div>
</x-app-layout>
