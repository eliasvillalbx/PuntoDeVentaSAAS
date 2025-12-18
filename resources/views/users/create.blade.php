<x-app-layout>
  {{-- Header --}}
  <x-slot name="header">
    <div class="flex items-center justify-between">
      <h1 class="text-xl sm:text-2xl font-semibold text-gray-900 flex items-center gap-2">
        <span class="material-symbols-outlined mi text-indigo-600">person_add</span>
        Nuevo Usuario
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

      <form method="POST" action="{{ route('users.store') }}" class="p-6 sm:p-8 space-y-10">
        @csrf

        {{-- Sección: Identidad y Acceso --}}
        <section class="space-y-6">
          <div class="border-b border-gray-100 pb-2">
            <h2 class="text-base font-bold text-gray-900 flex items-center gap-2">
              <span class="material-symbols-outlined mi text-indigo-500">badge</span>
              Información Personal y Perfil
            </h2>
            <p class="text-xs text-gray-500 mt-1">Datos básicos del usuario y asignación de nivel de acceso.</p>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
            {{-- Nombre --}}
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1.5">Nombre <span class="text-red-500">*</span></label>
              <input type="text" name="nombre" value="{{ old('nombre') }}" required
                     class="w-full h-11 rounded-xl border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm transition-all">
              @error('nombre') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Apellido Paterno --}}
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1.5">Apellido paterno <span class="text-red-500">*</span></label>
              <input type="text" name="apellido_paterno" value="{{ old('apellido_paterno') }}" required
                     class="w-full h-11 rounded-xl border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm transition-all">
            </div>

            {{-- Apellido Materno --}}
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1.5">Apellido materno</label>
              <input type="text" name="apellido_materno" value="{{ old('apellido_materno') }}"
                     class="w-full h-11 rounded-xl border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm transition-all">
            </div>

            {{-- Email --}}
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1.5">Correo electrónico <span class="text-red-500">*</span></label>
              <input type="email" name="email" value="{{ old('email') }}" required
                     class="w-full h-11 rounded-xl border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm transition-all"
                     placeholder="ejemplo@empresa.com">
            </div>

            {{-- Teléfono --}}
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1.5">Teléfono</label>
              <input type="text" name="telefono" value="{{ old('telefono') }}"
                     class="w-full h-11 rounded-xl border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm transition-all">
            </div>

            {{-- Selección de ROL --}}
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1.5">Rol de usuario <span class="text-red-500">*</span></label>
              <select name="role" required
                      class="w-full h-11 rounded-xl border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm transition-all">
                <option value="">Selecciona un rol...</option>
                @foreach ($roles as $role)
                  <option value="{{ $role->name }}" @selected(old('role') == $role->name)>
                    {{ ucwords(str_replace('_', ' ', $role->name)) }}
                  </option>
                @endforeach
              </select>
              @error('role') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Empresa (solo SA) --}}
            @if ($isSA)
              <div class="sm:col-span-2 lg:col-span-1">
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Asignar a Empresa <span class="text-red-500">*</span></label>
                <select name="id_empresa" required
                        class="w-full h-11 rounded-xl border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm transition-all">
                  <option value="">Selecciona empresa…</option>
                  @foreach ($empresas as $em)
                    <option value="{{ $em->id }}" @selected(old('id_empresa') == $em->id)>{{ $em->razon_social }}</option>
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
              <span class="material-symbols-outlined mi text-indigo-500">lock_open</span>
              Credenciales de Acceso
            </h2>
            <p class="text-xs text-gray-500 mt-1">Define la contraseña inicial del usuario.</p>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 max-w-2xl">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1.5">Contraseña <span class="text-red-500">*</span></label>
              <input type="password" name="password" required
                     class="w-full h-11 rounded-xl border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm transition-all">
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1.5">Confirmar contraseña <span class="text-red-500">*</span></label>
              <input type="password" name="password_confirmation" required
                     class="w-full h-11 rounded-xl border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm transition-all">
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
            <span class="material-symbols-outlined mi text-base">save</span>
            Crear Usuario
          </button>
        </div>
      </form>
    </div>
  </div>
</x-app-layout>