{{-- resources/views/categorias/show.blade.php --}}
<x-app-layout>
  {{-- Header --}}
  <x-slot name="header">
    <div class="flex items-center justify-between">
      <h1 class="text-xl sm:text-2xl font-semibold text-gray-900 flex items-center gap-2">
        <span class="material-symbols-outlined mi">label</span>
        {{ $categoria->nombre }}
      </h1>

      <div class="flex items-center gap-2">
        <a href="{{ route('categorias.edit', $categoria) }}"
           class="inline-flex items-center gap-2 h-10 px-4 rounded-xl border border-gray-300 text-gray-700 text-sm hover:bg-gray-50">
          <span class="material-symbols-outlined mi text-base">edit</span>
          Editar
        </a>
        <a href="{{ route('categorias.index') }}"
           class="inline-flex items-center gap-2 h-10 px-4 rounded-xl bg-gray-900 text-white text-sm font-medium hover:bg-gray-800">
          <span class="material-symbols-outlined mi text-base">arrow_back</span>
          Volver
        </a>
      </div>
    </div>
  </x-slot>

  <div class="max-w-7xl mx-auto space-y-6">

    {{-- Flash / errores --}}
    @if (session('success'))
      <div class="rounded-lg bg-green-50 text-green-800 ring-1 ring-green-200 p-3">
        {{ session('success') }}
      </div>
    @endif
    @if ($errors->any())
      <div class="rounded-lg bg-red-50 text-red-800 ring-1 ring-red-200 p-3">
        {{ $errors->first() }}
      </div>
    @endif

    {{-- Detalle de categoría --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
      <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between">
        <div class="flex items-start gap-3">
          <span class="material-symbols-outlined mi text-2xl text-gray-700">sell</span>
          <div>
            <div class="text-lg font-semibold text-gray-900">{{ $categoria->nombre }}</div>
            <div class="text-sm text-gray-500">
              Slug: <span class="font-mono">{{ $categoria->slug ?? '—' }}</span>
            </div>
          </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
          <span class="inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full
            {{ $categoria->activa ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
            <span class="material-symbols-outlined mi text-sm">{{ $categoria->activa ? 'check' : 'block' }}</span>
            {{ $categoria->activa ? 'Activa' : 'Inactiva' }}
          </span>
          <span class="inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-700">
            <span class="material-symbols-outlined mi text-sm">inventory_2</span>
            {{ isset($productos) ? $productos->total() : ($categoria->productos_count ?? $categoria->productos?->count() ?? 0) }} productos
          </span>
        </div>
      </div>

      <div class="px-6 py-6 grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <div class="text-xs text-gray-500 mb-1">Descripción</div>
          <div class="prose prose-sm max-w-none text-gray-700">
            {!! nl2br(e($categoria->descripcion ?? '—')) !!}
          </div>
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <div class="text-xs text-gray-500">Creado</div>
            <div class="text-sm text-gray-900">{{ $categoria->created_at?->format('Y-m-d H:i') }}</div>
          </div>
          <div>
            <div class="text-xs text-gray-500">Actualizado</div>
            <div class="text-sm text-gray-900">{{ $categoria->updated_at?->format('Y-m-d H:i') }}</div>
          </div>
        </div>
      </div>
    </div>

    {{-- Productos de la categoría: tarjetas --}}
    @php
      // Preferir $productos paginados si el controlador los envía, si no, usar relación
      $listado = $productos ?? $categoria->productos ?? collect();
    @endphp

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-2">
        <span class="material-symbols-outlined mi text-xl text-gray-700">view_comfy_alt</span>
        <h2 class="text-base font-semibold text-gray-900">Productos en esta categoría</h2>
      </div>

      @if(($listado instanceof \Illuminate\Support\Collection && $listado->isEmpty()) || (method_exists($listado,'count') && $listado->count() === 0))
        <div class="px-6 py-10 text-center text-gray-500">
          No hay productos en esta categoría.
        </div>
      @else
        <div class="px-6 py-6">
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            @foreach ($listado as $prod)
              <div class="group rounded-xl border border-gray-200 overflow-hidden bg-white hover:shadow-md transition">
                {{-- Imagen (usa storage si trae path/url; de lo contrario placeholder) --}}
                @php
                  $img = $prod->imagen_url
                          ?? ($prod->imagen_path ?? null
                              ? \Illuminate\Support\Facades\Storage::url($prod->imagen_path)
                              : null);
                @endphp
                <a href="{{ route('productos.show', $prod) }}" class="block">
                  <div class="aspect-[4/3] bg-gray-100 overflow-hidden">
                    @if($img)
                      <img src="{{ $img }}" alt="{{ $prod->nombre }}" class="w-full h-full object-cover group-hover:scale-[1.02] transition">
                    @else
                      <div class="w-full h-full flex items-center justify-center text-gray-400">
                        <span class="material-symbols-outlined mi text-5xl">photo</span>
                      </div>
                    @endif
                  </div>
                </a>

                <div class="p-4 space-y-3">
                  <div class="flex items-start justify-between gap-2">
                    <a href="{{ route('productos.show', $prod) }}" class="font-semibold text-gray-900 line-clamp-2 hover:underline">
                      {{ $prod->nombre }}
                    </a>
                    @if($prod->activo)
                      <span class="inline-flex items-center gap-1 text-[11px] px-2 py-0.5 rounded-full bg-green-100 text-green-700">
                        <span class="material-symbols-outlined mi text-sm">check</span> Activo
                      </span>
                    @else
                      <span class="inline-flex items-center gap-1 text-[11px] px-2 py-0.5 rounded-full bg-gray-100 text-gray-600">
                        <span class="material-symbols-outlined mi text-sm">block</span> Inactivo
                      </span>
                    @endif
                  </div>

                  <div class="flex items-center justify-between text-sm">
                    <div class="text-gray-900 font-medium">
                      {{ number_format($prod->precio, 2) }} {{ $prod->moneda_venta ?? 'MXN' }}
                    </div>
                    <div class="text-gray-600">
                      Stock: <span class="font-medium text-gray-900">{{ $prod->stock }}</span>
                    </div>
                  </div>

                  <div class="flex items-center justify-between text-xs text-gray-500">
                    <div class="truncate">SKU: {{ $prod->sku ?? '—' }}</div>
                    <a href="{{ route('productos.show', $prod) }}" class="inline-flex items-center gap-1 text-indigo-700 hover:text-indigo-900">
                      <span class="material-symbols-outlined mi text-sm">visibility</span> Ver
                    </a>
                  </div>
                </div>
              </div>
            @endforeach
          </div>

          {{-- Paginación si viene paginado desde el controlador --}}
          @if(method_exists($listado, 'links'))
            <div class="mt-6">
              {{ $listado->onEachSide(1)->links() }}
            </div>
          @endif
        </div>
      @endif
    </div>

  </div>
</x-app-layout>
