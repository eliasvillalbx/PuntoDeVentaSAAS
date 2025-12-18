<x-app-layout>
  {{-- Definimos el estado del modal aquí arriba --}}
  <div x-data="{ 
      showDeleteModal: false, 
      deleteUrl: '', 
      userName: '' 
  }">

    <x-slot name="header">
      <div class="flex items-center justify-between">
        <h1 class="text-xl sm:text-2xl font-semibold text-gray-900 flex items-center gap-2">
          <span class="material-symbols-outlined mi">group</span>
          Gestión de Usuarios
        </h1>
        <a href="{{ route('users.create') }}"
           class="inline-flex items-center gap-2 h-10 px-4 rounded-xl bg-indigo-600 text-white text-sm font-medium shadow hover:bg-indigo-700 transition-colors">
          <span class="material-symbols-outlined mi text-base">person_add</span>
          Nuevo Usuario
        </a>
      </div>
    </x-slot>

    <div class="max-w-7xl mx-auto space-y-6">
      
      {{-- Alertas (Success/Error) --}}
      @if (session('success'))
          <div class="p-4 rounded-xl bg-emerald-50 text-emerald-800 border border-emerald-200 flex items-center gap-2 text-sm font-medium">
              <span class="material-symbols-outlined text-base">check_circle</span>
              {{ session('success') }}
          </div>
      @endif
      @if (session('error'))
          <div class="p-4 rounded-xl bg-red-50 text-red-800 border border-red-200 flex items-center gap-2 text-sm font-medium">
              <span class="material-symbols-outlined text-base">error</span>
              {{ session('error') }}
          </div>
      @endif

      {{-- Filtros --}}
      <form method="GET" action="{{ route('users.index') }}"
            class="bg-white rounded-xl border border-gray-200 p-4 grid grid-cols-1 sm:grid-cols-2 {{ $isSA ? 'lg:grid-cols-5' : 'lg:grid-cols-3' }} gap-3">
        <div class="lg:col-span-2">
          <label class="block text-xs text-gray-600 mb-1">Buscar</label>
          <input type="text" name="q" value="{{ request('q') }}" placeholder="Nombre, email..."
                 class="w-full h-10 rounded-lg border-gray-300 text-sm focus:ring-indigo-500">
        </div>

        @if ($isSA)
          <div class="lg:col-span-2">
            <label class="block text-xs text-gray-600 mb-1">Empresa</label>
            <select name="empresa_id" class="w-full h-10 rounded-lg border-gray-300 text-sm focus:ring-indigo-500">
              <option value="">Todas</option>
              @foreach ($empresas as $em)
                <option value="{{ $em->id }}" @selected(request('empresa_id') == $em->id)>{{ $em->razon_social }}</option>
              @endforeach
            </select>
          </div>
        @endif

        <div class="flex items-end justify-end gap-2">
          <button type="submit" class="h-10 px-4 rounded-lg bg-gray-900 text-white text-sm hover:bg-gray-800">Aplicar</button>
        </div>
      </form>

      {{-- Tabla --}}
      <div class="bg-white rounded-xl border border-gray-200 overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
          <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-gray-700 border-b border-gray-100">
              <tr>
                <th class="px-4 py-3 text-left font-semibold">Usuario</th>
                <th class="px-4 py-3 text-left font-semibold">Rol</th>
                <th class="px-4 py-3 text-left font-semibold">Empresa</th>
                <th class="px-4 py-3 text-right font-semibold">Acciones</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              @forelse ($users as $u)
                <tr class="hover:bg-gray-50 transition-colors">
                  <td class="px-4 py-3">
                    <div class="font-medium text-gray-900">{{ $u->nombre_completo }}</div>
                    <div class="text-xs text-gray-500">{{ $u->email }}</div>
                  </td>
                  <td class="px-4 py-3">
                    @foreach($u->roles as $role)
                      <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider 
                        {{ $role->name == 'superadmin' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700' }}">
                        {{ str_replace('_', ' ', $role->name) }}
                      </span>
                    @endforeach
                  </td>
                  <td class="px-4 py-3 text-gray-600">
                    {{ $u->empresa?->razon_social ?? 'SISTEMA' }}
                  </td>
                  <td class="px-4 py-3 text-right">
                    <div class="flex justify-end gap-2">
                      <a href="{{ route('users.edit', $u) }}" class="p-1 text-gray-400 hover:text-indigo-600 transition-colors" title="Editar">
                          <span class="material-symbols-outlined text-xl">edit</span>
                      </a>
                      
                      {{-- Botón que abre el modal --}}
                      <button 
                          @click="showDeleteModal = true; deleteUrl = '{{ route('users.destroy', $u) }}'; userName = '{{ $u->nombre }}'"
                          class="p-1 text-gray-400 hover:text-red-600 transition-colors" title="Eliminar">
                          <span class="material-symbols-outlined text-xl">delete</span>
                      </button>
                    </div>
                  </td>
                </tr>
              @empty
                <tr><td colspan="4" class="p-8 text-center text-gray-500">No se encontraron usuarios.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
        <div class="p-4 border-t border-gray-100">{{ $users->links() }}</div>
      </div>

      {{-- ================= MODAL DE ELIMINACIÓN ================= --}}
      <div x-show="showDeleteModal" style="display: none;" 
           class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-sm"
           x-transition:enter="transition ease-out duration-300"
           x-transition:enter-start="opacity-0"
           x-transition:enter-end="opacity-100"
           x-transition:leave="transition ease-in duration-200"
           x-transition:leave-start="opacity-100"
           x-transition:leave-end="opacity-0">
           
          <div class="bg-white rounded-2xl shadow-xl max-w-sm w-full overflow-hidden"
               @click.away="showDeleteModal = false"
               x-transition:enter="transition ease-out duration-300"
               x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
               x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100">
               
              <div class="p-6 text-center">
                  <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-red-100 mb-4">
                      <span class="material-symbols-outlined text-red-600 text-2xl">warning</span>
                  </div>
                  <h3 class="text-lg font-bold text-gray-900">¿Eliminar usuario?</h3>
                  <p class="mt-2 text-sm text-gray-500">
                      Estás a punto de eliminar a <span x-text="userName" class="font-bold text-gray-800"></span>. Esta acción no se puede deshacer.
                  </p>
              </div>

              <div class="bg-gray-50 px-6 py-4 flex gap-3 justify-center">
                  <button @click="showDeleteModal = false" type="button" 
                          class="w-full inline-flex justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                      Cancelar
                  </button>
                  
                  <form x-bind:action="deleteUrl" method="POST" class="w-full">
                      @csrf @method('DELETE')
                      <button type="submit" 
                              class="w-full inline-flex justify-center rounded-lg border border-transparent bg-red-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                          Sí, eliminar
                      </button>
                  </form>
              </div>
          </div>
      </div>

    </div>
  </div>
</x-app-layout>