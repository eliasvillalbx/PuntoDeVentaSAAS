<x-app-layout>
  {{-- Header --}}
  <x-slot name="header">
    <div class="flex items-center justify-between">
      <h1 class="text-xl sm:text-2xl font-semibold text-gray-900 flex items-center gap-2">
        <span class="material-symbols-outlined mi">person_add</span>
        Nuevo cliente
      </h1>
      <a href="{{ route('clientes.index') }}"
         class="inline-flex items-center gap-2 h-10 px-4 rounded-xl border border-gray-300 text-gray-700 text-sm hover:bg-gray-50">
        <span class="material-symbols-outlined mi text-base">arrow_back</span>
        Volver
      </a>
    </div>
  </x-slot>

  <div class="max-w-5xl mx-auto">
    <div class="bg-white rounded-xl border border-gray-200 p-6">
      {{-- Errores --}}
      @if (session('error'))
        <div class="mb-4 rounded-lg bg-red-50 text-red-800 ring-1 ring-red-200 p-3">{{ session('error') }}</div>
      @endif
      @if ($errors->any())
        <div class="mb-4 rounded-lg bg-red-50 text-red-800 ring-1 ring-red-200 p-3">{{ $errors->first() }}</div>
      @endif

      <form x-data="{ tipo: 'fisica' }" method="POST" action="{{ route('clientes.store') }}" class="space-y-8">
        @csrf

        {{-- Empresa (solo SA) --}}
        @if ($isSA)
          <section class="space-y-2">
            <h2 class="text-sm font-semibold text-gray-800 flex items-center gap-2">
              <span class="material-symbols-outlined mi text-base">apartment</span>
              Empresa
            </h2>
            <div>
              <label class="block text-sm text-gray-700 mb-1">Empresa <span class="text-red-600">*</span></label>
              <select name="empresa_id" class="w-full h-10 rounded-lg border-gray-300 focus:ring-indigo-500 text-sm">
                <option value="">Selecciona empresa…</option>
                @foreach ($empresas as $em)
                  <option value="{{ $em->id }}" @selected(old('empresa_id') == $em->id)>
                    {{ $em->nombre_comercial ?? $em->razon_social }}
                  </option>
                @endforeach
              </select>
              @error('empresa_id') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
          </section>
        @else
          {{-- Empresa fija para no-SA --}}
          <input type="hidden" name="empresa_id" value="{{ $empresaId }}">
        @endif

        {{-- Identidad --}}
        <section class="space-y-4">
          <h2 class="text-sm font-semibold text-gray-800 flex items-center gap-2">
            <span class="material-symbols-outlined mi text-base">badge</span>
            Identidad
          </h2>

          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div>
              <label class="block text-sm text-gray-700 mb-1">Tipo de persona</label>
              <select x-model="tipo" name="tipo_persona" class="w-full h-10 rounded-lg border-gray-300 focus:ring-indigo-500 text-sm">
                <option value="fisica">Física</option>
                <option value="moral">Moral</option>
              </select>
            </div>

            <template x-if="tipo==='fisica'">
              <div class="col-span-full grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div>
                  <label class="block text-sm text-gray-700 mb-1">Nombre <span class="text-red-600">*</span></label>
                  <input type="text" name="nombre" value="{{ old('nombre') }}" class="w-full h-10 rounded-lg border-gray-300 focus:ring-indigo-500 text-sm">
                </div>
                <div>
                  <label class="block text-sm text-gray-700 mb-1">Apellido paterno <span class="text-red-600">*</span></label>
                  <input type="text" name="apellido_paterno" value="{{ old('apellido_paterno') }}" class="w-full h-10 rounded-lg border-gray-300 focus:ring-indigo-500 text-sm">
                </div>
                <div>
                  <label class="block text-sm text-gray-700 mb-1">Apellido materno</label>
                  <input type="text" name="apellido_materno" value="{{ old('apellido_materno') }}" class="w-full h-10 rounded-lg border-gray-300 focus:ring-indigo-500 text-sm">
                </div>
              </div>
            </template>

            <template x-if="tipo==='moral'">
              <div class="col-span-full">
                <label class="block text-sm text-gray-700 mb-1">Razón social <span class="text-red-600">*</span></label>
                <input type="text" name="razon_social" value="{{ old('razon_social') }}" class="w-full h-10 rounded-lg border-gray-300 focus:ring-indigo-500 text-sm">
              </div>
            </template>
          </div>
        </section>

        {{-- Contacto --}}
        <section class="space-y-4">
          <h2 class="text-sm font-semibold text-gray-800 flex items-center gap-2">
            <span class="material-symbols-outlined mi text-base">mail</span>
            Contacto
          </h2>

          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div>
              <label class="block text-sm text-gray-700 mb-1">RFC</label>
              <input type="text" name="rfc" value="{{ old('rfc') }}" class="w-full h-10 rounded-lg border-gray-300 focus:ring-indigo-500 text-sm" placeholder="Opcional">
            </div>
            <div>
              <label class="block text-sm text-gray-700 mb-1">Email</label>
              <input type="email" name="email" value="{{ old('email') }}" class="w-full h-10 rounded-lg border-gray-300 focus:ring-indigo-500 text-sm" placeholder="Opcional">
            </div>
            <div>
              <label class="block text-sm text-gray-700 mb-1">Teléfono</label>
              <input type="text" name="telefono" value="{{ old('telefono') }}" class="w-full h-10 rounded-lg border-gray-300 focus:ring-indigo-500 text-sm" placeholder="Opcional">
            </div>
            <div>
              <label class="block text-sm text-gray-700 mb-1">Activo</label>
              <select name="activo" class="w-full h-10 rounded-lg border-gray-300 focus:ring-indigo-500 text-sm">
                <option value="1" selected>Sí</option>
                <option value="0">No</option>
              </select>
            </div>
          </div>
        </section>

        {{-- Dirección --}}
        <section class="space-y-4">
          <h2 class="text-sm font-semibold text-gray-800 flex items-center gap-2">
            <span class="material-symbols-outlined mi text-base">pin_drop</span>
            Dirección
          </h2>

          <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
            <div class="md:col-span-3">
              <label class="block text-sm text-gray-700 mb-1">Calle</label>
              <input type="text" name="calle" value="{{ old('calle') }}" class="w-full h-10 rounded-lg border-gray-300 focus:ring-indigo-500 text-sm">
            </div>
            <div>
              <label class="block text-sm text-gray-700 mb-1">No. Ext.</label>
              <input type="text" name="numero_ext" value="{{ old('numero_ext') }}" class="w-full h-10 rounded-lg border-gray-300 focus:ring-indigo-500 text-sm">
            </div>
            <div>
              <label class="block text-sm text-gray-700 mb-1">No. Int.</label>
              <input type="text" name="numero_int" value="{{ old('numero_int') }}" class="w-full h-10 rounded-lg border-gray-300 focus:ring-indigo-500 text-sm">
            </div>
            <div>
              <label class="block text-sm text-gray-700 mb-1">C.P.</label>
              <input type="text" name="cp" value="{{ old('cp') }}" class="w-full h-10 rounded-lg border-gray-300 focus:ring-indigo-500 text-sm">
            </div>
            <div class="md:col-span-2">
              <label class="block text-sm text-gray-700 mb-1">Colonia</label>
              <input type="text" name="colonia" value="{{ old('colonia') }}" class="w-full h-10 rounded-lg border-gray-300 focus:ring-indigo-500 text-sm">
            </div>
            <div class="md:col-span-2">
              <label class="block text-sm text-gray-700 mb-1">Municipio</label>
              <input type="text" name="municipio" value="{{ old('municipio') }}" class="w-full h-10 rounded-lg border-gray-300 focus:ring-indigo-500 text-sm">
            </div>
            <div class="md:col-span-2">
              <label class="block text-sm text-gray-700 mb-1">Estado</label>
              <input type="text" name="estado" value="{{ old('estado') }}" class="w-full h-10 rounded-lg border-gray-300 focus:ring-indigo-500 text-sm">
            </div>
          </div>
        </section>

        {{-- Acciones --}}
        <div class="flex items-center justify-end gap-2">
          <a href="{{ route('clientes.index') }}"
             class="inline-flex items-center gap-2 h-10 px-4 rounded-xl border border-gray-300 text-gray-700 text-sm hover:bg-gray-50">
            <span class="material-symbols-outlined mi text-base">close</span>
            Cancelar
          </a>
          <button type="submit"
                  class="inline-flex items-center gap-2 h-10 px-4 rounded-xl bg-indigo-600 text-white text-sm font-medium shadow hover:bg-indigo-700">
            <span class="material-symbols-outlined mi text-base">save</span>
            Guardar
          </button>
        </div>
      </form>
    </div>
  </div>
</x-app-layout>
