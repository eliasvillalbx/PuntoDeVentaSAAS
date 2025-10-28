<x-app-layout>
  <x-slot name="header">
    <div class="flex items-center justify-between">
      <h1 class="text-xl sm:text-2xl font-semibold text-gray-900 flex items-center gap-2">
        <span class="material-symbols-outlined mi">add</span>
        Nueva categoría
      </h1>
      <a href="{{ route('categorias.index') }}"
         class="inline-flex items-center gap-2 h-10 px-4 rounded-xl border border-gray-300 text-gray-700 text-sm hover:bg-gray-50">
        <span class="material-symbols-outlined mi text-base">arrow_back</span>
        Volver
      </a>
    </div>
  </x-slot>

  <div class="max-w-3xl mx-auto">
    <div class="bg-white rounded-xl border border-gray-200 p-6">
      @if (session('error'))
        <div class="mb-4 rounded-lg bg-red-50 text-red-800 ring-1 ring-red-200 p-3">{{ session('error') }}</div>
      @endif
      @if ($errors->any())
        <div class="mb-4 rounded-lg bg-red-50 text-red-800 ring-1 ring-red-200 p-3">{{ $errors->first() }}</div>
      @endif

      <form method="POST" action="{{ route('categorias.store') }}" class="space-y-6">
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

        <div>
          <label class="block text-sm text-gray-700 mb-1">Nombre <span class="text-red-600">*</span></label>
          <input type="text" name="nombre" value="{{ old('nombre') }}"
                 class="w-full h-10 rounded-lg border-gray-300 focus:ring-indigo-500 text-sm" required>
          @error('nombre') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
          <label class="block text-sm text-gray-700 mb-1">Descripción</label>
          <textarea name="descripcion" rows="4" class="w-full rounded-lg border-gray-300 focus:ring-indigo-500 text-sm">{{ old('descripcion') }}</textarea>
          @error('descripcion') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>

        <div class="flex items-center gap-2">
          <input type="checkbox" name="activa" value="1" id="activa"
                 class="h-4 w-4 text-indigo-600 border-gray-300 rounded" @checked(old('activa', true))>
          <label for="activa" class="text-sm text-gray-700">Activa</label>
        </div>

        <div class="flex items-center justify-end gap-2">
          <a href="{{ route('categorias.index') }}" class="h-10 px-4 rounded-xl border text-sm">Cancelar</a>
          <button type="submit" class="h-10 px-4 rounded-xl bg-indigo-600 text-white text-sm font-medium">Guardar</button>
        </div>
      </form>
    </div>
  </div>
</x-app-layout>
