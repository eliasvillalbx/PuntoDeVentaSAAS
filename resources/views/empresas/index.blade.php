{{-- resources/views/empresas/index.blade.php --}}
<x-app-layout>
  @php
    // Nota: no uses 'use ...;' aquí. Si necesitas clases, llama con el FQCN.
    $isActive = fn ($v) => request('q') === $v ? 'text-indigo-600 font-semibold' : 'text-gray-500';
  @endphp

  {{-- Header --}}
  <x-slot name="header">
    <div class="flex items-center justify-between">
      <h1 class="text-xl sm:text-2xl font-semibold text-gray-900 flex items-center gap-2">
        <span class="material-symbols-outlined mi">apartment</span>
        Empresas
      </h1>
      <a href="{{ route('empresas.create') }}"
         class="inline-flex items-center gap-2 h-10 px-4 rounded-xl bg-indigo-600 text-white text-sm font-medium shadow hover:bg-indigo-700 transition">
        <span class="material-symbols-outlined mi text-base">add</span>
        Nueva empresa
      </a>
    </div>
  </x-slot>

  <div class="max-w-7xl mx-auto space-y-6">
    {{-- Filtros / Búsqueda --}}
    <form method="GET" action="{{ route('empresas.index') }}"
          class="bg-white rounded-xl border border-gray-200 p-4 flex flex-col sm:flex-row gap-3 items-start sm:items-center">
      <div class="relative w-full sm:max-w-md">
        <span class="material-symbols-outlined mi absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">search</span>
        <input type="text" name="q" value="{{ $q }}" placeholder="Buscar por razón social, nombre comercial o RFC…"
               class="w-full pl-10 pr-3 h-10 rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm" />
      </div>

      <div class="flex gap-2">
        <button type="submit"
                class="inline-flex items-center gap-2 h-10 px-3 rounded-lg bg-gray-900 text-white text-sm hover:bg-black/90">
          <span class="material-symbols-outlined mi text-base">tune</span>
          Aplicar
        </button>
        <a href="{{ route('empresas.index') }}"
           class="inline-flex items-center gap-2 h-10 px-3 rounded-lg border border-gray-300 text-sm text-gray-700 hover:bg-gray-50">
          <span class="material-symbols-outlined mi text-base">close</span>
          Limpiar
        </a>
      </div>
    </form>

    {{-- Tabla --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead class="bg-gray-50 text-gray-700">
            <tr>
              <th class="px-4 py-3 text-left font-medium">Razón social</th>
              <th class="px-4 py-3 text-left font-medium">Nombre comercial</th>
              <th class="px-4 py-3 text-left font-medium">RFC</th>
              <th class="px-4 py-3 text-left font-medium">Tipo</th>
              <th class="px-4 py-3 text-left font-medium">Email</th>
              <th class="px-4 py-3 text-left font-medium">Teléfono</th>
              <th class="px-4 py-3 text-left font-medium">Activa</th>
              <th class="px-4 py-3 text-right font-medium">Acciones</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            @forelse ($empresas as $e)
              <tr class="hover:bg-gray-50">
                <td class="px-4 py-3">
                  <div class="flex items-center gap-3">
                    @php
                      $path = $e->logo_path;
                      $isDirectUrl = $path && \Illuminate\Support\Str::startsWith($path, ['http://', 'https://', '/storage/']);
                      $existsOnDisk = $path && !$isDirectUrl && \Illuminate\Support\Facades\Storage::disk('public')->exists($path);
                      $logoUrl = $isDirectUrl
                        ? $path
                        : ($existsOnDisk ? \Illuminate\Support\Facades\Storage::url($path) : null);
                    @endphp

                    @if($logoUrl)
                      <img
                        src="{{ $logoUrl }}"
                        alt="Logo de {{ $e->nombre_comercial ?: $e->razon_social }}"
                        class="h-9 w-9 rounded-lg object-cover ring-1 ring-gray-200"
                        loading="lazy"
                        referrerpolicy="no-referrer"
                      >
                    @else
                      <div class="h-9 w-9 rounded-lg bg-gray-100 ring-1 ring-gray-200 grid place-items-center">
                        <span class="material-symbols-outlined text-gray-500 text-[18px]">apartment</span>
                      </div>
                    @endif

                    <div class="font-medium text-gray-900">{{ $e->razon_social }}</div>
                  </div>
                </td>

                <td class="px-4 py-3 text-gray-700">{{ $e->nombre_comercial ?: '—' }}</td>
                <td class="px-4 py-3 text-gray-700">{{ $e->rfc }}</td>

                <td class="px-4 py-3">
                  <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-xs ring-1 ring-gray-200
                               {{ $e->tipo_persona === 'moral' ? 'bg-blue-50 text-blue-700' : 'bg-emerald-50 text-emerald-700' }}">
                    <span class="material-symbols-outlined mi text-sm">
                      {{ $e->tipo_persona === 'moral' ? 'domain' : 'person' }}
                    </span>
                    {{ ucfirst($e->tipo_persona) }}
                  </span>
                </td>

                <td class="px-4 py-3 text-gray-700">{{ $e->email ?: '—' }}</td>
                <td class="px-4 py-3 text-gray-700">{{ $e->telefono ?: '—' }}</td>

                <td class="px-4 py-3">
                  @if($e->activa)
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-xs bg-green-50 text-green-700 ring-1 ring-green-200">
                      <span class="material-symbols-outlined mi text-sm">check_circle</span> Activa
                    </span>
                  @else
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-xs bg-red-50 text-red-700 ring-1 ring-red-200">
                      <span class="material-symbols-outlined mi text-sm">error</span> Inactiva
                    </span>
                  @endif
                </td>

                <td class="px-4 py-3">
                  <div class="flex justify-end gap-1">
                    <a href="{{ route('empresas.show', $e) }}"
                       class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-gray-700 hover:bg-gray-100"
                       title="Ver">
                      <span class="material-symbols-outlined mi text-base">visibility</span>
                    </a>
                    <a href="{{ route('empresas.edit', $e) }}"
                       class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-gray-700 hover:bg-gray-100"
                       title="Editar">
                      <span class="material-symbols-outlined mi text-base">edit</span>
                    </a>
                    <form x-data method="POST" action="{{ route('empresas.destroy', $e) }}" onsubmit="return false;">
                      @csrf
                      @method('DELETE')
                      <button
                        @click="
                          if (confirm('¿Eliminar la empresa {{ addslashes($e->razon_social) }}? Esta acción no se puede deshacer.')) {
                            $root.submit();
                          }
                        "
                        type="submit"
                        class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-red-700 hover:bg-red-50"
                        title="Eliminar">
                        <span class="material-symbols-outlined mi text-base">delete</span>
                      </button>
                    </form>
                  </div>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="8" class="px-4 py-8 text-center text-gray-500">
                  No hay empresas registradas.
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      {{-- Paginación --}}
      <div class="px-4 py-3 border-t border-gray-100 bg-white">
        {{ $empresas->onEachSide(1)->links() }}
      </div>
    </div>
  </div>
</x-app-layout>
