<x-app-layout>
  <x-slot name="header">
    <div class="flex items-center justify-between">
      <h1 class="text-xl sm:text-2xl font-semibold text-gray-900 flex items-center gap-2">
        <span class="material-symbols-outlined mi">group</span>
        Gestión de Usuarios
      </h1>
      <a href="{{ route('users.create') }}"
         class="inline-flex items-center gap-2 h-10 px-4 rounded-xl bg-indigo-600 text-white text-sm font-medium shadow hover:bg-indigo-700">
        <span class="material-symbols-outlined mi text-base">person_add</span>
        Nuevo Usuario
      </a>
    </div>
  </x-slot>

  <div class="max-w-7xl mx-auto space-y-6">
    {{-- Filtros --}}
    <form method="GET" action="{{ route('users.index') }}"
          class="bg-white rounded-xl border border-gray-200 p-4 grid grid-cols-1 sm:grid-cols-2 {{ $isSA ? 'lg:grid-cols-5' : 'lg:grid-cols-3' }} gap-3">
      <div class="lg:col-span-2">
        <label class="block text-xs text-gray-600 mb-1">Buscar</label>
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Nombre, email..."
               class="w-full h-10 rounded-lg border-gray-300 text-sm">
      </div>

      @if ($isSA)
        <div class="lg:col-span-2">
          <label class="block text-xs text-gray-600 mb-1">Empresa</label>
          <select name="empresa_id" class="w-full h-10 rounded-lg border-gray-300 text-sm">
            <option value="">Todas</option>
            @foreach ($empresas as $em)
              <option value="{{ $em->id }}" @selected(request('empresa_id') == $em->id)>{{ $em->razon_social }}</option>
            @endforeach
          </select>
        </div>
      @endif

      <div class="flex items-end justify-end gap-2">
        <button type="submit" class="h-10 px-4 rounded-lg bg-gray-900 text-white text-sm">Aplicar</button>
      </div>
    </form>

    {{-- Tabla --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden shadow-sm">
      <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead class="bg-gray-50 text-gray-700">
            <tr>
              <th class="px-4 py-3 text-left">Usuario</th>
              <th class="px-4 py-3 text-left">Rol</th>
              <th class="px-4 py-3 text-left">Empresa</th>
              <th class="px-4 py-3 text-right">Acciones</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            @forelse ($users as $u)
              <tr class="hover:bg-gray-50">
                <td class="px-4 py-3">
                  <div class="font-medium text-gray-900">{{ $u->nombre_completo }}</div>
                  <div class="text-xs text-gray-500">{{ $u->email }}</div>
                </td>
                <td class="px-4 py-3">
                  @foreach($u->roles as $role)
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider 
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
                    <a href="{{ route('users.edit', $u) }}" class="p-1 text-gray-400 hover:text-indigo-600">
                        <span class="material-symbols-outlined text-xl">edit</span>
                    </a>
                    <form action="{{ route('users.destroy', $u) }}" method="POST" onsubmit="return confirm('¿Eliminar?')">
                        @csrf @method('DELETE')
                        <button class="p-1 text-gray-400 hover:text-red-600">
                            <span class="material-symbols-outlined text-xl">delete</span>
                        </button>
                    </form>
                  </div>
                </td>
              </tr>
            @empty
              <tr><td colspan="4" class="p-8 text-center text-gray-500">No se encontraron usuarios.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
      <div class="p-4 border-t">{{ $users->links() }}</div>
    </div>
  </div>
</x-app-layout>