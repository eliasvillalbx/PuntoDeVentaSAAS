<x-app-layout>
  {{-- Header --}}
  <x-slot name="header">
    <div class="flex items-center justify-between">
      <h1 class="text-xl sm:text-2xl font-semibold text-gray-900 flex items-center gap-2">
        <span class="material-symbols-outlined mi text-indigo-600">edit_square</span>
        Editar Usuario: {{ $user->nombre }}
      </h1>
      <a href="{{ route('users.index') }}"
         class="inline-flex items-center gap-2 h-10 px-4 rounded-xl border border-gray-300 text-gray-700 text-sm hover:bg-gray-50 transition-colors">
        <span class="material-symbols-outlined mi text-base">arrow_back</span>
        Volver
      </a>
    </div>
  </x-slot>

  <div class="max-w-5xl mx-auto pb-12">
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
      
      {{-- Mensajes de Error --}}
      @if ($errors->any())
        <div class="p-4 bg-red-50 border-b border-red-100 flex items-start gap-3">
          <span class="material-symbols-outlined text-red-600">error</span>
          <div class="text-sm text-red-800 font-medium">
            {{ $errors->first() }}
          </div>
        </div>
      @endif

      @php $isSA = auth()->user()->hasRole('superadmin'); @endphp

      <form method="POST" action="{{ route('users.update', $user) }}" class="p-6 sm:p-8 space-y-10">
        @csrf
        @method('PUT')

        {{-- Sección: Identidad y Perfil --}}
        <section class="space-y-6">
          <div class="border-b border-gray-100 pb-2">
            <h2 class="text-base font-bold text-gray-900 flex items-center gap-2">
              <span class="material-symbols-outlined mi text-indigo-500">person</span>
              Información de Perfil
            </h2>
            <p class="text-xs text-gray-500 mt-1">Actualiza los datos personales y el nivel de acceso al sistema.</p>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
            {{-- Nombre --}}
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1.5">Nombre <span class="text-red-500">*</span></label>
              <input type="text" name="nombre" value="{{ old('nombre', $user->nombre) }}" required
                     class="w-full h-11 rounded-xl border-gray-300 focus:ring-2 focus:ring-indigo-500 text-sm transition-all">
            </div>

            {{-- Apellido Paterno --}}
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1.5">Apellido paterno <span class="text-red-500">*</span></label>
              <input type="text" name="apellido_paterno" value="{{ old('apellido_paterno', $user->apellido_paterno) }}" required
                     class="w-full h-11 rounded-xl border-gray-300 focus:ring-2 focus:ring-indigo-500 text-sm transition-all">
            </div>

            {{-- Apellido Materno --}}
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1.5">Apellido materno</label>
              <input type="text" name="apellido_materno" value="{{ old('apellido_materno', $user->apellido_materno) }}"
                     class="w-full h-11 rounded-xl border-gray-300 focus:ring-2 focus:ring-indigo-500 text-sm transition-all">
            </div>

            {{-- Email --}}
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1.5">Correo electrónico <span class="text-red-500">*</span></label>
              <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                     class="w-full h-11 rounded-xl border-gray-300 focus:ring-2 focus:ring-indigo-500 text-sm transition-all">
            </div>

            {{-- Teléfono --}}
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1.5">Teléfono</label>
              <input type="text" name="telefono" value="{{ old('telefono', $user->telefono) }}"
                     class="w-full h-11 rounded-xl border-gray-300 focus:ring-2 focus:ring-indigo-500 text-sm transition-all">
            </div>

            {{-- Selección de ROL --}}
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1.5">Rol de usuario <span class="text-red-500">*</span></label>
              <select name="role" required
                      class="w-full h-11 rounded-xl border-gray-300 focus:ring-2 focus:ring-indigo-500 text-sm transition-all">
                @foreach ($roles as $role)
                  <option value="{{ $role->name }}" @selected(old('role', $user->roles->first()?->name) == $role->name)>
                    {{ ucwords(str_replace('_', ' ', $role->name)) }}
                  </option>
                @endforeach
              </select>
            </div>

            {{-- Empresa (solo SA) --}}
            @if ($isSA)
              <div class="sm:col-span-2 lg:col-span-1">
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Empresa <span class="text-red-500">*</span></label>
                <select name="id_empresa" required
                        class="w-full h-11 rounded-xl border-gray-300 focus:ring-2 focus:ring-indigo-500 text-sm transition-all">
                  @foreach ($empresas as $em)
                    <option value="{{ $em->id }}" @selected(old('id_empresa', $user->id_empresa) == $em->id)>{{ $em->razon_social }}</option>
                  @endforeach
                </select>
              </div>
            @endif
          </div>
        </section>

        {{-- Sección: Seguridad --}}
        <section class="space-y-6">
          <div class="border-b border-gray-100 pb-2">
            <h2 class="text-base font-bold text-gray-900 flex items-center gap-2">
              <span class="material-symbols-outlined mi text-indigo-500">lock_reset</span>
              Cambiar Contraseña
            </h2>
            <p class="text-[11px] text-amber-600 font-medium mt-1 bg-amber-50 px-2 py-1 rounded-md inline-block border border-amber-100">
              <span class="material-symbols-outlined text-[14px] align-middle">info</span>
              Deja estos campos en blanco si no deseas cambiar la contraseña actual.
            </p>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 max-w-2xl">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1.5">Nueva contraseña</label>
              <input type="password" name="password" 
                     placeholder="••••••••"
                     class="w-full h-11 rounded-xl border-gray-300 focus:ring-2 focus:ring-indigo-500 text-sm transition-all">
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1.5">Confirmar nueva contraseña</label>
              <input type="password" name="password_confirmation"
                     placeholder="••••••••"
                     class="w-full h-11 rounded-xl border-gray-300 focus:ring-2 focus:ring-indigo-500 text-sm transition-all">
            </div>
          </div>
        </section>

        {{-- Botones de Acción --}}
        <div class="flex items-center justify-end gap-3 pt-6 border-t border-gray-100">
          <a href="{{ route('users.index') }}"
             class="inline-flex items-center gap-2 h-11 px-6 rounded-xl border border-gray-300 text-gray-700 text-sm font-medium hover:bg-gray-50 transition-all">
            Cancelar
          </a>
          <button type="submit"
                  class="inline-flex items-center gap-2 h-11 px-8 rounded-xl bg-indigo-600 text-white text-sm font-semibold shadow-md shadow-indigo-200 hover:bg-indigo-700 focus:ring-4 focus:ring-indigo-100 transition-all">
            <span class="material-symbols-outlined mi text-base">check_circle</span>
            Actualizar Usuario
          </button>
        </div>
      </form>
    </div>
  </div>
</x-app-layout>