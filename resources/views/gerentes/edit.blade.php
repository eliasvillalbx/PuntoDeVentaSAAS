<x-app-layout>
  {{-- Header --}}
  <x-slot name="header">
    <div class="flex items-center justify-between">
      <h1 class="text-xl sm:text-2xl font-semibold text-gray-900 flex items-center gap-2">
        <span class="material-symbols-outlined mi">edit</span>
        Editar gerente
      </h1>
      <a href="{{ route('gerentes.index') }}"
         class="inline-flex items-center gap-2 h-10 px-4 rounded-xl border border-gray-300 text-gray-700 text-sm hover:bg-gray-50">
        <span class="material-symbols-outlined mi text-base">arrow_back</span>
        Volver
      </a>
    </div>
  </x-slot>

  <div class="max-w-5xl mx-auto">
    <div class="bg-white rounded-xl border border-gray-200 p-6">
      {{-- Mensajes --}}
      @if (session('error'))
        <div class="mb-4 rounded-lg bg-red-50 text-red-800 ring-1 ring-red-200 p-3">{{ session('error') }}</div>
      @endif
      @if ($errors->any())
        <div class="mb-4 rounded-lg bg-red-50 text-red-800 ring-1 ring-red-200 p-3">{{ $errors->first() }}</div>
      @endif

      @php $isSA = auth()->user()->hasRole('superadmin'); @endphp

      <form method="POST" action="{{ route('gerentes.update', $gerente) }}" class="space-y-8">
        @csrf
        @method('PUT')

        {{-- Identidad --}}
        <section class="space-y-4">
          <h2 class="text-sm font-semibold text-gray-800 flex items-center gap-2">
            <span class="material-symbols-outlined mi text-base">badge</span>
            Identidad
          </h2>

          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div>
              <label class="block text-sm text-gray-700 mb-1">Nombre <span class="text-red-600">*</span></label>
              <input type="text" name="nombre" value="{{ old('nombre', $gerente->nombre) }}"
                     class="w-full h-10 rounded-lg border-gray-300 focus:ring-indigo-500 text-sm">
              @error('nombre') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
              <label class="block text-sm text-gray-700 mb-1">Apellido paterno <span class="text-red-600">*</span></label>
              <input type="text" name="apellido_paterno" value="{{ old('apellido_paterno', $gerente->apellido_paterno) }}"
                     class="w-full h-10 rounded-lg border-gray-300 focus:ring-indigo-500 text-sm">
              @error('apellido_paterno') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
              <label class="block text-sm text-gray-700 mb-1">Apellido materno</label>
              <input type="text" name="apellido_materno" value="{{ old('apellido_materno', $gerente->apellido_materno) }}"
                     class="w-full h-10 rounded-lg border-gray-300 focus:ring-indigo-500 text-sm">
              @error('apellido_materno') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
              <label class="block text-sm text-gray-700 mb-1">Teléfono</label>
              <input type="text" name="telefono" value="{{ old('telefono', $gerente->telefono) }}"
                     class="w-full h-10 rounded-lg border-gray-300 focus:ring-indigo-500 text-sm">
              @error('telefono') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
              <label class="block text-sm text-gray-700 mb-1">Email <span class="text-red-600">*</span></label>
              <input type="email" name="email" value="{{ old('email', $gerente->email) }}"
                     class="w-full h-10 rounded-lg border-gray-300 focus:ring-indigo-500 text-sm">
              @error('email') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Empresa (solo SA) --}}
            @if ($isSA)
              <div>
                <label class="block text-sm text-gray-700 mb-1">Empresa <span class="text-red-600">*</span></label>
                <select name="id_empresa" class="w-full h-10 rounded-lg border-gray-300 focus:ring-indigo-500 text-sm">
                  @foreach ($empresas as $em)
                    <option value="{{ $em->id }}" @selected(old('id_empresa', $gerente->id_empresa) == $em->id)>
                      {{ $em->razon_social }}
                    </option>
                  @endforeach
                </select>
                @error('id_empresa') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
              </div>
            @endif
          </div>
        </section>

        {{-- Seguridad (opcional) --}}
        <section class="space-y-2">
          <h2 class="text-sm font-semibold text-gray-800 flex items-center gap-2">
            <span class="material-symbols-outlined mi text-base">lock_reset</span>
            Seguridad
          </h2>
          <p class="text-xs text-gray-500">Deja en blanco para mantener la contraseña actual.</p>

          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mt-2">
            <div>
              <label class="block text-sm text-gray-700 mb-1">Nueva contraseña</label>
              <input type="password" name="password"
                     class="w-full h-10 rounded-lg border-gray-300 focus:ring-indigo-500 text-sm">
              @error('password') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
              <label class="block text-sm text-gray-700 mb-1">Confirmar nueva contraseña</label>
              <input type="password" name="password_confirmation"
                     class="w-full h-10 rounded-lg border-gray-300 focus:ring-indigo-500 text-sm">
            </div>
          </div>
        </section>

        {{-- Acciones --}}
        <div class="flex items-center justify-end gap-2">
          <a href="{{ route('gerentes.index') }}"
             class="inline-flex items-center gap-2 h-10 px-4 rounded-xl border border-gray-300 text-gray-700 text-sm hover:bg-gray-50">
            <span class="material-symbols-outlined mi text-base">close</span>
            Cancelar
          </a>
          <button type="submit"
                  class="inline-flex items-center gap-2 h-10 px-4 rounded-xl bg-indigo-600 text-white text-sm font-medium shadow hover:bg-indigo-700">
            <span class="material-symbols-outlined mi text-base">save</span>
            Actualizar
          </button>
        </div>
      </form>
    </div>
  </div>
</x-app-layout>
