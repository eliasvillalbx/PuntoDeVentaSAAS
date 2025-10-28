<x-app-layout>
  <x-slot name="header">
    <div class="flex items-center justify-between">
      <h1 class="text-xl sm:text-2xl font-semibold text-gray-900 flex items-center gap-2">
        <span class="material-symbols-outlined mi">edit_square</span>
        Editar administrador
      </h1>
      <a href="{{ route('admin-empresas.index') }}"
         class="inline-flex items-center gap-2 h-10 px-4 rounded-xl border text-sm text-gray-700 hover:bg-gray-50">
        <span class="material-symbols-outlined mi text-base">arrow_back</span>
        Volver
      </a>
    </div>
  </x-slot>

  <div class="max-w-3xl mx-auto">
    @if (session('success'))
      <div class="mb-4 rounded-lg bg-green-50 text-green-800 ring-1 ring-green-200 p-3">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
      <div class="mb-4 rounded-lg bg-red-50 text-red-800 ring-1 ring-red-200 p-3">{{ $errors->first() }}</div>
    @endif

    <div class="bg-white rounded-xl border border-gray-200 p-6">
      <form method="POST" action="{{ route('admin-empresas.update', $admin) }}" class="space-y-6">
        @csrf @method('PUT')

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm text-gray-700 mb-1">Nombre <span class="text-red-600">*</span></label>
            <input type="text" name="nombre" value="{{ old('nombre', $admin->nombre) }}"
                   class="w-full h-10 rounded-lg border-gray-300 focus:ring-indigo-500 text-sm" required>
            @error('nombre') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
          </div>
          <div>
            <label class="block text-sm text-gray-700 mb-1">Apellido paterno <span class="text-red-600">*</span></label>
            <input type="text" name="apellido_paterno" value="{{ old('apellido_paterno', $admin->apellido_paterno) }}"
                   class="w-full h-10 rounded-lg border-gray-300 focus:ring-indigo-500 text-sm" required>
            @error('apellido_paterno') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
          </div>
          <div>
            <label class="block text-sm text-gray-700 mb-1">Apellido materno</label>
            <input type="text" name="apellido_materno" value="{{ old('apellido_materno', $admin->apellido_materno) }}"
                   class="w-full h-10 rounded-lg border-gray-300 focus:ring-indigo-500 text-sm">
            @error('apellido_materno') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
          </div>
          <div>
            <label class="block text-sm text-gray-700 mb-1">Teléfono</label>
            <input type="text" name="telefono" value="{{ old('telefono', $admin->telefono) }}"
                   class="w-full h-10 rounded-lg border-gray-300 focus:ring-indigo-500 text-sm">
            @error('telefono') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
          </div>

          <div>
            <label class="block text-sm text-gray-700 mb-1">Email <span class="text-red-600">*</span></label>
            <input type="email" name="email" value="{{ old('email', $admin->email) }}"
                   class="w-full h-10 rounded-lg border-gray-300 focus:ring-indigo-500 text-sm" required>
            @error('email') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
          </div>

          <div>
            <label class="block text-sm text-gray-700 mb-1">Nueva contraseña</label>
            <input type="password" name="password"
                   class="w-full h-10 rounded-lg border-gray-300 focus:ring-indigo-500 text-sm"
                   placeholder="Dejar en blanco para no cambiar">
            @error('password') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
          </div>
          <div>
            <label class="block text-sm text-gray-700 mb-1">Confirmar nueva contraseña</label>
            <input type="password" name="password_confirmation"
                   class="w-full h-10 rounded-lg border-gray-300 focus:ring-indigo-500 text-sm"
                   placeholder="Repite la nueva contraseña">
          </div>

          <div class="sm:col-span-2">
            <label class="block text-sm text-gray-700 mb-1">Empresa <span class="text-red-600">*</span></label>
            <select name="id_empresa"
                    class="w-full h-10 rounded-lg border-gray-300 focus:ring-indigo-500 text-sm" required>
              @foreach ($empresas as $e)
                <option value="{{ $e->id }}" @selected(old('id_empresa', $admin->id_empresa) == $e->id)>{{ $e->razon_social }}</option>
              @endforeach
            </select>
            @error('id_empresa') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
          </div>
        </div>

        <div class="flex justify-end gap-2">
          <a href="{{ route('admin-empresas.index') }}" class="h-10 px-4 rounded-lg border text-sm text-gray-700">Cancelar</a>
          <button type="submit" class="h-10 px-4 rounded-lg bg-indigo-600 text-white text-sm">Guardar cambios</button>
        </div>
      </form>
    </div>
  </div>
</x-app-layout>
