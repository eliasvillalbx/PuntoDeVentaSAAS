<x-app-layout>
  {{-- Header --}}
  <x-slot name="header">
    <div class="flex items-center justify-between">
      <h1 class="text-xl sm:text-2xl font-semibold text-gray-900 flex items-center gap-2">
        <span class="material-symbols-outlined mi">supervisor_account</span>
        Gerentes
      </h1>
      <a href="{{ route('gerentes.create') }}"
         class="inline-flex items-center gap-2 h-10 px-4 rounded-xl bg-indigo-600 text-white text-sm font-medium shadow hover:bg-indigo-700">
        <span class="material-symbols-outlined mi text-base">person_add</span>
        Nuevo gerente
      </a>
    </div>
  </x-slot>

  <div class="max-w-7xl mx-auto space-y-6">
    {{-- Filtros --}}
    @php $isSA = auth()->user()->hasRole('superadmin'); @endphp
    <form method="GET" action="{{ route('gerentes.index') }}"
          class="bg-white rounded-xl border border-gray-200 p-4 grid grid-cols-1 sm:grid-cols-2 {{ $isSA ? 'lg:grid-cols-5' : 'lg:grid-cols-3' }} gap-3">
      <div class="{{ $isSA ? 'lg:col-span-2' : 'lg:col-span-2' }}">
        <label class="block text-xs text-gray-600 mb-1">Buscar</label>
        <input type="text" name="q" value="{{ $q ?? '' }}" placeholder="Nombre, apellidos, email o teléfono…"
               class="w-full h-10 rounded-lg border-gray-300 focus:ring-indigo-500 text-sm">
      </div>

      {{-- Empresa solo para SA --}}
      @if ($isSA)
        <div class="lg:col-span-2">
          <label class="block text-xs text-gray-600 mb-1">Empresa</label>
          <select name="empresa_id" class="w-full h-10 rounded-lg border-gray-300 focus:ring-indigo-500 text-sm">
            <option value="">Todas</option>
            @foreach ($empresas as $em)
              <option value="{{ $em->id }}" @selected(($empresaId ?? null) == $em->id)>{{ $em->razon_social }}</option>
            @endforeach
          </select>
        </div>
      @endif

      <div class="flex items-end justify-end gap-2">
        <button type="submit" class="h-10 px-4 rounded-lg bg-gray-900 text-white text-sm">Aplicar</button>
        <a href="{{ route('gerentes.index') }}" class="h-10 px-4 rounded-lg border text-sm text-gray-700">Limpiar</a>
      </div>
    </form>

    {{-- Tabla --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead class="bg-gray-50 text-gray-700">
            <tr>
              <th class="px-4 py-3 text-left">Gerente</th>
              <th class="px-4 py-3 text-left">Email</th>
              <th class="px-4 py-3 text-left">Teléfono</th>
              <th class="px-4 py-3 text-left">Empresa</th>
              <th class="px-4 py-3 text-left">Creado</th>
              <th class="px-4 py-3 text-right">Acciones</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            @forelse ($gerentes as $u)
              <tr class="hover:bg-gray-50">
                <td class="px-4 py-3 text-gray-900 font-medium">
                  {{ $u->nombre_completo ?: trim("{$u->nombre} {$u->apellido_paterno} {$u->apellido_materno}") }}
                </td>
                <td class="px-4 py-3 text-gray-700">{{ $u->email }}</td>
                <td class="px-4 py-3 text-gray-700">{{ $u->telefono ?? '—' }}</td>
                <td class="px-4 py-3 text-gray-700">{{ $u->empresa?->razon_social ?? '—' }}</td>
                <td class="px-4 py-3 text-gray-700">{{ $u->created_at?->format('Y-m-d H:i') }}</td>
                <td class="px-4 py-3">
                  <div class="flex justify-end gap-1">
                    <a href="{{ route('gerentes.show', $u) }}"
                       class="px-2.5 py-1.5 rounded-lg text-gray-700 hover:bg-gray-100" title="Ver">
                      <span class="material-symbols-outlined mi text-base">visibility</span>
                    </a>
                    <a href="{{ route('gerentes.edit', $u) }}"
                       class="px-2.5 py-1.5 rounded-lg text-gray-700 hover:bg-gray-100" title="Editar">
                      <span class="material-symbols-outlined mi text-base">edit</span>
                    </a>
                    <form method="POST" action="{{ route('gerentes.destroy', $u) }}"
                          onsubmit="return confirm('¿Eliminar gerente?');">
                      @csrf @method('DELETE')
                      <button type="submit" class="px-2.5 py-1.5 rounded-lg text-red-700 hover:bg-red-50" title="Eliminar">
                        <span class="material-symbols-outlined mi text-base">delete</span>
                      </button>
                    </form>
                  </div>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="6" class="px-4 py-8 text-center text-gray-500">No hay gerentes registrados.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div class="px-4 py-3 border-t border-gray-100 bg-white">
        {{ $gerentes->onEachSide(1)->links() }}
      </div>
    </div>
  </div>
</x-app-layout>
