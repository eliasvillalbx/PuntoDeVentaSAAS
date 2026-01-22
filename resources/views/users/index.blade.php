<x-app-layout>
  <div x-data="{ showDeleteModal:false, deleteUrl:'', userName:'' }">
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
        @else
          <div class="lg:col-span-1">
            <label class="block text-xs text-gray-600 mb-1">Empresa</label>
            <div class="w-full h-10 rounded-lg border border-gray-200 bg-gray-50 flex items-center px-3 text-sm text-gray-700">
              {{ $miEmpresa?->razon_social ?? '—' }}
            </div>
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
                    <div class="font-medium text-gray-900">
                      {{ $u->nombre }} {{ $u->apellido_paterno }} {{ $u->apellido_materno }}
                    </div>
                    <div class="text-xs text-gray-500">{{ $u->email }}</div>
                  </td>

                  <td class="px-4 py-3">
                    @foreach($u->roles as $role)
                      <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider
                        {{ $role->name == 'superadmin' ? 'bg-purple-100 text-purple-700' : ($role->name == 'administrador_empresa' ? 'bg-blue-100 text-blue-700' : ($role->name == 'gerente' ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-700')) }}">
                        {{ str_replace('_', ' ', $role->name) }}
                      </span>
                    @endforeach
                  </td>

                  <td class="px-4 py-3 text-gray-600">
                    {{ $u->empresa?->razon_social ?? 'SISTEMA' }}
                  </td>

                  <td class="px-4 py-3 text-right">
                    <div class="flex justify-end gap-2">
                      <a href="{{ route('users.edit', $u) }}"
                         class="p-1 text-gray-400 hover:text-indigo-600 transition-colors" title="Editar">
                        <span class="material-symbols-outlined text-xl">edit</span>
                      </a>
                    </div>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="4" class="p-8 text-center text-gray-500">No se encontraron usuarios.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>

        <div class="p-4 border-t border-gray-100">{{ $users->links() }}</div>
      </div>
    </div>
  </div>
</x-app-layout>
