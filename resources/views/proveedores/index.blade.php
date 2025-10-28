<x-app-layout>
  <x-slot name="header">
    <div class="flex items-center justify-between">
      <h1 class="text-xl sm:text-2xl font-semibold text-gray-900 flex items-center gap-2">
        <span class="material-symbols-outlined mi">local_shipping</span>
        Proveedores
      </h1>
      <a href="{{ route('proveedores.create') }}"
         class="inline-flex items-center gap-2 h-10 px-4 rounded-xl bg-indigo-600 text-white text-sm font-medium shadow hover:bg-indigo-700">
        <span class="material-symbols-outlined mi text-base">add</span>
        Nuevo proveedor
      </a>
    </div>
  </x-slot>



  <div class="max-w-7xl mx-auto space-y-6">
    <form method="GET" action="{{ route('proveedores.index') }}"
          class="bg-white rounded-xl border border-gray-200 p-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
      <div class="lg:col-span-2">
        <label class="block text-xs text-gray-600 mb-1">Buscar</label>
        <input type="text" name="q" value="{{ $q ?? '' }}" placeholder="Nombre, RFC, email, teléfono, contacto…"
               class="w-full h-10 rounded-lg border-gray-300 focus:ring-indigo-500 text-sm">
      </div>
      @if(isset($empresas) && $empresas->count())
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
      <div>
        <label class="block text-xs text-gray-600 mb-1">Activo</label>
        <select name="activo" class="w-full h-10 rounded-lg border-gray-300 focus:ring-indigo-500 text-sm">
          <option value="">Todos</option>
          <option value="1" @selected(($activo ?? '')==='1')>Sí</option>
          <option value="0" @selected(($activo ?? '')==='0')>No</option>
        </select>
      </div>
      <div class="flex items-end justify-end gap-2">
        <button type="submit" class="h-10 px-4 rounded-lg bg-gray-900 text-white text-sm">Aplicar</button>
        <a href="{{ route('proveedores.index') }}" class="h-10 px-4 rounded-lg border text-sm text-gray-700">Limpiar</a>
      </div>
    </form>

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead class="bg-gray-50 text-gray-700">
            <tr>
              <th class="px-4 py-3 text-left">Nombre</th>
              <th class="px-4 py-3 text-left">RFC</th>
              <th class="px-4 py-3 text-left">Contacto</th>
              <th class="px-4 py-3 text-left">Email</th>
              <th class="px-4 py-3 text-left">Teléfono</th>
              <th class="px-4 py-3 text-left">Estado</th>
              <th class="px-4 py-3 text-right">Acciones</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            @forelse ($proveedores as $p)
              <tr class="hover:bg-gray-50">
                <td class="px-4 py-3 text-gray-900 font-medium">
                  <a href="{{ route('proveedores.show', $p) }}" class="hover:underline">{{ $p->nombre }}</a>
                </td>
                <td class="px-4 py-3 text-gray-700">{{ $p->rfc ?: '—' }}</td>
                <td class="px-4 py-3 text-gray-700">{{ $p->contacto ?: '—' }}</td>
                <td class="px-4 py-3 text-gray-700">{{ $p->email ?: '—' }}</td>
                <td class="px-4 py-3 text-gray-700">{{ $p->telefono ?: '—' }}</td>
                <td class="px-4 py-3">
                  <span class="inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full
                    {{ $p->activo ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                    <span class="material-symbols-outlined mi text-sm">{{ $p->activo ? 'check' : 'block' }}</span>
                    {{ $p->activo ? 'Activo' : 'Inactivo' }}
                  </span>
                </td>
                <td class="px-4 py-3">
                  <div class="flex justify-end gap-1">
                    <a href="{{ route('proveedores.edit', $p) }}"
                       class="px-2.5 py-1.5 rounded-lg text-gray-700 hover:bg-gray-100" title="Editar">
                      <span class="material-symbols-outlined mi text-base">edit</span>
                    </a>
                    <form method="POST" action="{{ route('proveedores.destroy', $p) }}"
                          onsubmit="return confirm('¿Eliminar proveedor?');">
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
                <td colspan="7" class="px-4 py-8 text-center text-gray-500">No hay proveedores registrados.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
      <div class="px-4 py-3 border-t border-gray-100 bg-white">
        {{ $proveedores->onEachSide(1)->links() }}
      </div>
    </div>
  </div>
</x-app-layout>
