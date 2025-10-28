<x-app-layout>
  <x-slot name="header">
    <div class="flex items-center justify-between">
      <h1 class="text-xl sm:text-2xl font-semibold text-gray-900 flex items-center gap-2">
        <span class="material-symbols-outlined mi">receipt_long</span>
        Detalle de suscripción
      </h1>
      <div class="flex items-center gap-2">
        @if($suscripcion->estado === 'vencida' || $suscripcion->fecha_vencimiento->isPast())
          <form method="POST" action="{{ route('suscripciones.renew', $suscripcion) }}"
                onsubmit="return confirm('¿Renovar suscripción?');">
            @csrf
            <button type="submit" class="inline-flex items-center gap-2 h-10 px-4 rounded-xl bg-emerald-600 text-white text-sm hover:bg-emerald-700">
              <span class="material-symbols-outlined mi text-base">autorenew</span>
              Renovar
            </button>
          </form>
        @endif
        <a href="{{ route('suscripciones.edit', $suscripcion) }}"
           class="inline-flex items-center gap-2 h-10 px-4 rounded-xl bg-indigo-600 text-white text-sm hover:bg-indigo-700">
          <span class="material-symbols-outlined mi text-base">edit</span>
          Editar
        </a>
        <a href="{{ route('suscripciones.index') }}"
           class="inline-flex items-center gap-2 h-10 px-4 rounded-xl border text-sm text-gray-700 hover:bg-gray-50">
          <span class="material-symbols-outlined mi text-base">arrow_back</span>
          Volver
        </a>
      </div>
    </div>
  </x-slot>

  {{-- Mensajes --}}
  <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 mt-3">
    @if (session('success'))
      <div class="mb-3 rounded-lg bg-green-50 text-green-800 ring-1 ring-green-200 p-3" role="alert">
        {{ session('success') }}
      </div>
    @endif
    @if ($errors->any())
      <div class="mb-3 rounded-lg bg-red-50 text-red-800 ring-1 ring-red-200 p-3" role="alert">
        {{ $errors->first() }}
      </div>
    @endif
  </div>

  @php
    $planLabel = [
      '1_mes'   => '1 mes',
      '6_meses' => '6 meses',
      '1_año'   => '1 año',
      '3_años'  => '3 años',
    ];
  @endphp

  <div class="max-w-4xl mx-auto space-y-6">
    <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-6">
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <div class="text-xs text-gray-500">Empresa</div>
          <div class="text-sm text-gray-900 font-medium">{{ $suscripcion->empresa?->razon_social }}</div>
        </div>

        <div>
          <div class="text-xs text-gray-500">Plan</div>
          <div class="text-sm text-gray-900">{{ $planLabel[$suscripcion->plan] ?? $suscripcion->plan }}</div>
        </div>

        <div>
          <div class="text-xs text-gray-500">Fecha de inicio</div>
          <div class="text-sm text-gray-900">{{ $suscripcion->fecha_inicio?->format('Y-m-d') }}</div>
        </div>

        <div>
          <div class="text-xs text-gray-500">Fecha de vencimiento</div>
          <div class="text-sm text-gray-900">{{ $suscripcion->fecha_vencimiento?->format('Y-m-d') }}</div>
        </div>

        <div>
          <div class="text-xs text-gray-500">Estado</div>
          @php
            $badge = $suscripcion->estado === 'activa'
              ? 'bg-green-50 text-green-700 ring-green-200'
              : 'bg-red-50 text-red-700 ring-red-200';
          @endphp
          <div>
            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-xs ring-1 {{ $badge }}">
              {{ ucfirst($suscripcion->estado) }}
              @if($suscripcion->renovado)
                <span class="ml-1 text-[10px] opacity-70">(renovada)</span>
              @endif
            </span>
          </div>
        </div>

        <div>
          <div class="text-xs text-gray-500">Renovada</div>
          <div class="text-sm text-gray-900">{{ $suscripcion->renovado ? 'Sí' : 'No' }}</div>
        </div>
      </div>

      <div class="pt-4 border-t border-gray-100 text-xs text-gray-500">
        <div>Creada: {{ $suscripcion->created_at?->format('Y-m-d H:i') }}</div>
        <div>Actualizada: {{ $suscripcion->updated_at?->format('Y-m-d H:i') }}</div>
      </div>
    </div>
  </div>
</x-app-layout>
