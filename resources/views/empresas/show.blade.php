{{-- resources/views/empresas/show.blade.php --}}
<x-app-layout>
  {{-- Header --}}
  <x-slot name="header">
    <div class="flex items-center justify-between">
      <h1 class="text-xl sm:text-2xl font-semibold text-gray-900 flex items-center gap-2">
        <span class="material-symbols-outlined mi">visibility</span>
        Detalle de empresa
      </h1>
      <div class="flex items-center gap-2">
        <a href="{{ route('empresas.index') }}"
           class="inline-flex items-center gap-2 h-10 px-4 rounded-xl border border-gray-300 text-gray-700 text-sm hover:bg-gray-50">
          <span class="material-symbols-outlined mi text-base">arrow_back</span>
          Volver
        </a>
        <a href="{{ route('empresas.edit', $empresa) }}"
           class="inline-flex items-center gap-2 h-10 px-4 rounded-xl bg-indigo-600 text-white text-sm font-medium shadow hover:bg-indigo-700">
          <span class="material-symbols-outlined mi text-base">edit</span>
          Editar
        </a>
      </div>
    </div>
  </x-slot>

  <div class="max-w-5xl mx-auto space-y-6">
    <div class="bg-white rounded-xl border border-gray-200 p-6">
      {{-- Header con logo y razón social --}}
      <div class="flex items-center gap-4 pb-4 border-b border-gray-100">
        @php
          $path = $empresa->logo_path;
          $isDirectUrl = $path && \Illuminate\Support\Str::startsWith($path, ['http://','https://','/storage/']);
          $existsOnDisk = $path && !$isDirectUrl && \Illuminate\Support\Facades\Storage::disk('public')->exists($path);
          $logoUrl = $isDirectUrl ? $path : ($existsOnDisk ? \Illuminate\Support\Facades\Storage::url($path) : null);
        @endphp

        @if ($logoUrl)
          <img src="{{ $logoUrl }}" alt="Logo de {{ $empresa->nombre_comercial ?: $empresa->razon_social }}"
               class="h-14 w-14 rounded-xl object-cover ring-1 ring-gray-200" loading="lazy">
        @else
          <div class="h-14 w-14 rounded-xl bg-gray-100 ring-1 ring-gray-200 grid place-items-center">
            <span class="material-symbols-outlined text-gray-500 text-2xl">apartment</span>
          </div>
        @endif

        <div class="min-w-0">
          <div class="text-lg font-semibold text-gray-900">{{ $empresa->razon_social }}</div>
          <div class="text-sm text-gray-500">{{ $empresa->nombre_comercial ?: '—' }}</div>
        </div>

        <div class="ml-auto">
          @if($empresa->activa)
            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-xs bg-green-50 text-green-700 ring-1 ring-green-200">
              <span class="material-symbols-outlined mi text-sm">check_circle</span> Activa
            </span>
          @else
            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-xs bg-red-50 text-red-700 ring-1 ring-red-200">
              <span class="material-symbols-outlined mi text-sm">error</span> Inactiva
            </span>
          @endif
        </div>
      </div>

      {{-- Contenido --}}
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6">
        {{-- Identidad --}}
        <section class="lg:col-span-1 space-y-2">
          <h2 class="text-sm font-semibold text-gray-800 flex items-center gap-2">
            <span class="material-symbols-outlined mi text-base">badge</span>
            Identidad
          </h2>
          <div class="text-sm text-gray-600 space-y-1">
            <div><span class="text-gray-500">RFC:</span> <span class="font-mono">{{ $empresa->rfc }}</span></div>
            <div>
              <span class="text-gray-500">Tipo de persona:</span>
              <span class="ml-1">{{ ucfirst($empresa->tipo_persona) }}</span>
            </div>
            <div><span class="text-gray-500">Régimen fiscal:</span> {{ $empresa->regimen_fiscal_code ?: '—' }}</div>
            <div><span class="text-gray-500">Sitio web:</span>
              @if($empresa->sitio_web)
                <a href="{{ $empresa->sitio_web }}" target="_blank" class="text-indigo-600 hover:underline break-all">
                  {{ $empresa->sitio_web }}
                </a>
              @else
                —
              @endif
            </div>
          </div>
        </section>

        {{-- Contacto --}}
        <section class="lg:col-span-1 space-y-2">
          <h2 class="text-sm font-semibold text-gray-800 flex items-center gap-2">
            <span class="material-symbols-outlined mi text-base">contact_mail</span>
            Contacto
          </h2>
          <div class="text-sm text-gray-600 space-y-1">
            <div><span class="text-gray-500">Email:</span>
              @if($empresa->email)
                <a href="mailto:{{ $empresa->email }}" class="text-indigo-600 hover:underline">{{ $empresa->email }}</a>
              @else
                —
              @endif
            </div>
            <div><span class="text-gray-500">Teléfono:</span>
              @if($empresa->telefono)
                <a href="tel:{{ $empresa->telefono }}" class="text-indigo-600 hover:underline">{{ $empresa->telefono }}</a>
              @else
                —
              @endif
            </div>
            <div><span class="text-gray-500">Zona horaria:</span> {{ $empresa->timezone }}</div>
          </div>
        </section>

        {{-- Dirección --}}
        <section class="lg:col-span-1 space-y-2">
          <h2 class="text-sm font-semibold text-gray-800 flex items-center gap-2">
            <span class="material-symbols-outlined mi text-base">home_pin</span>
            Dirección
          </h2>
          <div class="text-sm text-gray-600 space-y-1">
            <div><span class="text-gray-500">Calle:</span> {{ $empresa->calle ?: '—' }}</div>
            <div class="grid grid-cols-3 gap-2">
              <div><span class="text-gray-500">Num. ext.:</span> {{ $empresa->numero_exterior ?: '—' }}</div>
              <div><span class="text-gray-500">Num. int.:</span> {{ $empresa->numero_interior ?: '—' }}</div>
              <div><span class="text-gray-500">CP:</span> {{ $empresa->codigo_postal ?: '—' }}</div>
            </div>
            <div><span class="text-gray-500">Colonia:</span> {{ $empresa->colonia ?: '—' }}</div>
            <div class="grid grid-cols-2 gap-2">
              <div><span class="text-gray-500">Municipio:</span> {{ $empresa->municipio ?: '—' }}</div>
              <div><span class="text-gray-500">Ciudad:</span> {{ $empresa->ciudad ?: '—' }}</div>
            </div>
            <div class="grid grid-cols-2 gap-2">
              <div><span class="text-gray-500">Estado:</span> {{ $empresa->estado ?: '—' }}</div>
              <div><span class="text-gray-500">País:</span> {{ $empresa->pais }}</div>
            </div>
          </div>
        </section>
      </div>

      <div class="mt-6 pt-4 border-t border-gray-100 text-xs text-gray-500">
        <div>Creada: {{ $empresa->created_at?->format('Y-m-d H:i') }}</div>
        <div>Actualizada: {{ $empresa->updated_at?->format('Y-m-d H:i') }}</div>
      </div>
    </div>
  </div>
</x-app-layout>
