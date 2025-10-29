{{-- resources/views/proveedores/show.blade.php --}}
<x-app-layout>
  {{-- Header --}}
  <x-slot name="header">
    <div class="flex items-center justify-between">
      <h1 class="text-xl sm:text-2xl font-semibold text-gray-900 flex items-center gap-2">
        <span class="material-symbols-outlined mi">local_shipping</span>
        {{ $proveedor->nombre }}
      </h1>

      <div class="flex items-center gap-2">
        <a href="{{ route('proveedores.edit', $proveedor) }}"
           class="inline-flex items-center gap-2 h-10 px-4 rounded-xl border border-gray-300 text-gray-700 text-sm hover:bg-gray-50">
          <span class="material-symbols-outlined mi text-base">edit</span>
          Editar
        </a>
        <a href="{{ route('proveedores.index') }}"
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

    {{-- Card: Datos del proveedor (sin sitio web, sin contacto y sin dirección) --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
      <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between">
        <div class="flex items-start gap-3">
          <span class="material-symbols-outlined mi text-2xl text-gray-700">warehouse</span>
          <div>
            <div class="text-lg font-semibold text-gray-900">{{ $proveedor->nombre }}</div>
            <div class="text-sm text-gray-500">RFC: {{ $proveedor->rfc ?: '—' }}</div>
          </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
          <span class="inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full
            {{ $proveedor->activo ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
            <span class="material-symbols-outlined mi text-sm">{{ $proveedor->activo ? 'check' : 'block' }}</span>
            {{ $proveedor->activo ? 'Activo' : 'Inactivo' }}
          </span>

          @if(isset($proveedor->empresa) || isset($empresa))
            <span class="inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-700">
              <span class="material-symbols-outlined mi text-sm">apartment</span>
              {{ $proveedor->empresa->razon_social ?? $empresa->razon_social ?? 'Empresa' }}
            </span>
          @endif

          @php
            $totalProductos = null;
            if (isset($productos) && method_exists($productos, 'total')) {
              $totalProductos = $productos->total();
            } elseif (method_exists($proveedor, 'productos')) {
              $totalProductos = $proveedor->productos()->count();
            }
          @endphp
          @if(!is_null($totalProductos))
            <span class="inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full bg-purple-50 text-purple-700">
              <span class="material-symbols-outlined mi text-sm">inventory_2</span>
              {{ $totalProductos }} productos
            </span>
          @endif
        </div>
      </div>

      <div class="px-6 py-6 grid grid-cols-1 md:grid-cols-2 gap-6">
        {{-- Columna 1: datos básicos (sin contacto) --}}
        <div class="space-y-4">
          <div>
            <div class="text-xs text-gray-500">Email</div>
            <div class="text-sm text-gray-900">{{ $proveedor->email ?: '—' }}</div>
          </div>
          <div>
            <div class="text-xs text-gray-500">Teléfono</div>
            <div class="text-sm text-gray-900">{{ $proveedor->telefono ?: '—' }}</div>
          </div>
        </div>

        {{-- Columna 2: metadatos --}}
        <div class="grid grid-cols-2 gap-4">
          <div>
            <div class="text-xs text-gray-500">Creado</div>
            <div class="text-sm text-gray-900">{{ $proveedor->created_at?->format('Y-m-d H:i') }}</div>
          </div>
          <div>
            <div class="text-xs text-gray-500">Actualizado</div>
            <div class="text-sm text-gray-900">{{ $proveedor->updated_at?->format('Y-m-d H:i') }}</div>
          </div>
        </div>
      </div>
    </div>

    {{-- Productos que suministra este proveedor (tarjetas) --}}
    @php
      $listado = $productos ?? ($proveedor->relationLoaded('productos') ? $proveedor->productos : (method_exists($proveedor,'productos') ? $proveedor->productos()->get() : collect()));
    @endphp

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-2">
        <span class="material-symbols-outlined mi text-xl text-gray-700">view_comfy_alt</span>
        <h2 class="text-base font-semibold text-gray-900">Productos de este proveedor</h2>
      </div>

      @if(($listado instanceof \Illuminate\Support\Collection && $listado->isEmpty()) || (method_exists($listado,'count') && $listado->count() === 0))
        <div class="px-6 py-10 text-center text-gray-500">
          No hay productos vinculados a este proveedor.
        </div>
      @else
        <div class="px-6 py-6">
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            @foreach ($listado as $prod)
              <div class="group rounded-xl border border-gray-200 overflow-hidden bg-white hover:shadow-md transition">
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

                  <div class="flex flex-col gap-1 text-sm">
                    <div class="flex items-center justify-between">
                      <div class="text-gray-900 font-medium">
                        {{ number_format($prod->precio, 2) }} {{ $prod->moneda_venta ?? 'MXN' }}
                      </div>
                      <div class="text-gray-600">
                        Stock: <span class="font-medium text-gray-900">{{ $prod->stock }}</span>
                      </div>
                    </div>

                    @if(isset($prod->pivot))
                      <div class="flex flex-wrap items-center gap-2 mt-1">
                        <span class="inline-flex items-center gap-1 text-[11px] px-2 py-0.5 rounded-full bg-blue-50 text-blue-700">
                          <span class="material-symbols-outlined mi text-sm">sell</span>
                          Costo: {{ number_format($prod->pivot->costo ?? 0, 2) }} {{ $prod->pivot->moneda ?? 'MXN' }}
                        </span>
                        @if(!empty($prod->pivot->moq))
                          <span class="inline-flex items-center gap-1 text-[11px] px-2 py-0.5 rounded-full bg-amber-50 text-amber-700" title="Cantidad mínima de compra">
                            <span class="material-symbols-outlined mi text-sm">counter_1</span> MOQ: {{ $prod->pivot->moq }}
                          </span>
                        @endif
                        @if(!empty($prod->pivot->lead_time_dias))
                          <span class="inline-flex items-center gap-1 text-[11px] px-2 py-0.5 rounded-full bg-purple-50 text-purple-700" title="Tiempo de entrega estimado">
                            <span class="material-symbols-outlined mi text-sm">schedule</span> {{ $prod->pivot->lead_time_dias }} días
                          </span>
                        @endif
                        @if(!empty($prod->pivot->sku_proveedor))
                          <span class="inline-flex items-center gap-1 text-[11px] px-2 py-0.5 rounded-full bg-gray-50 text-gray-700">
                            <span class="material-symbols-outlined mi text-sm">badge</span> SKU prov: {{ $prod->pivot->sku_proveedor }}
                          </span>
                        @endif
                      </div>
                    @endif
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
