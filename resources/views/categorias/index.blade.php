{{-- resources/views/categorias/index.blade.php --}}
<x-app-layout>
  <x-slot name="header">
    <div class="flex items-center justify-between">
      <h1 class="text-xl sm:text-2xl font-semibold text-gray-900 flex items-center gap-2">
        <span class="material-symbols-outlined mi">label</span>
        Categorías
      </h1>
      <a href="{{ route('categorias.create') }}"
         class="inline-flex items-center gap-2 h-10 px-4 rounded-xl bg-indigo-600 text-white text-sm font-medium shadow hover:bg-indigo-700">
        <span class="material-symbols-outlined mi text-base">add</span>
        Nueva categoría
      </a>
    </div>
  </x-slot>

  <div class="max-w-7xl mx-auto space-y-6">
    {{-- Filtros --}}
    <form method="GET" action="{{ route('categorias.index') }}"
          class="bg-white rounded-xl border border-gray-200 p-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
      <div class="lg:col-span-2">
        <label class="block text-xs text-gray-600 mb-1">Buscar</label>
        <input type="text" name="q" value="{{ $q ?? '' }}" placeholder="Nombre o descripción…"
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
        <label class="block text-xs text-gray-600 mb-1">Activa</label>
        <select name="activa" class="w-full h-10 rounded-lg border-gray-300 focus:ring-indigo-500 text-sm">
          <option value="">Todas</option>
          <option value="1" @selected(($activa ?? '')==='1')>Sí</option>
          <option value="0" @selected(($activa ?? '')==='0')>No</option>
        </select>
      </div>

      <div class="lg:col-span-5 flex items-end justify-end gap-2">
        <button type="submit" class="h-10 px-4 rounded-lg bg-gray-900 text-white text-sm">Aplicar</button>
        <a href="{{ route('categorias.index') }}" class="h-10 px-4 rounded-lg border text-sm text-gray-700">Limpiar</a>
      </div>
    </form>

    {{-- Tabla --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead class="bg-gray-50 text-gray-700">
            <tr>
              <th class="px-4 py-3 text-left">Nombre</th>
              <th class="px-4 py-3 text-left">Descripción</th>
              <th class="px-4 py-3 text-left">Estado</th>
              <th class="px-4 py-3 text-left">Creado</th>
              <th class="px-4 py-3 text-right">Acciones</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            @forelse ($cats as $c)
              <tr class="hover:bg-gray-50">
                <td class="px-4 py-3 text-gray-900 font-medium">
                  <a href="{{ route('categorias.show', $c) }}" class="hover:underline">{{ $c->nombre }}</a>
                </td>
                <td class="px-4 py-3 text-gray-700 line-clamp-1">{{ Str::limit($c->descripcion, 80) }}</td>
                <td class="px-4 py-3">
                  <span class="inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full
                    {{ $c->activa ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                    <span class="material-symbols-outlined mi text-sm">{{ $c->activa ? 'check' : 'block' }}</span>
                    {{ $c->activa ? 'Activa' : 'Inactiva' }}
                  </span>
                </td>
                <td class="px-4 py-3 text-gray-700">{{ $c->created_at?->format('Y-m-d H:i') }}</td>
                <td class="px-4 py-3">
                  <div class="flex justify-end gap-1">
                    {{-- Ver --}}
                    <a href="{{ route('categorias.show', $c) }}"
                       class="px-2.5 py-1.5 rounded-lg text-gray-700 hover:bg-gray-100" title="Ver">
                      <span class="material-symbols-outlined mi text-base">visibility</span>
                    </a>
                    {{-- Editar --}}
                    <a href="{{ route('categorias.edit', $c) }}"
                       class="px-2.5 py-1.5 rounded-lg text-gray-700 hover:bg-gray-100" title="Editar">
                      <span class="material-symbols-outlined mi text-base">edit</span>
                    </a>
                    {{-- Eliminar --}}
                    <form method="POST" action="{{ route('categorias.destroy', $c) }}"
                          onsubmit="return confirm('¿Eliminar categoría?');">
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
                <td colspan="5" class="px-4 py-8 text-center text-gray-500">No hay categorías registradas.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div class="px-4 py-3 border-t border-gray-100 bg-white">
        {{ $cats->onEachSide(1)->links() }}
      </div>
    </div>
  </div>
</x-app-layout>
