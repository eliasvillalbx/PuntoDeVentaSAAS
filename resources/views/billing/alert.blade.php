{{-- resources/views/billing/alert.blade.php --}}

<x-app-layout>
  <div class="min-h-screen bg-gradient-to-br from-indigo-50 via-white to-purple-50 flex items-center justify-center py-16 px-6">

    {{-- Contenedor principal --}}
    <div class="w-full max-w-4xl relative">
      {{-- Glow decorativo --}}
      <div aria-hidden="true" class="absolute -inset-3 blur-2xl bg-gradient-to-tr from-indigo-200 via-fuchsia-200 to-amber-200 opacity-60 rounded-3xl"></div>

      {{-- Card --}}
      <div class="relative bg-white/90 backdrop-blur rounded-3xl shadow-xl ring-1 ring-gray-200/70 overflow-hidden">

        {{-- Top bar / acento --}}
        <div class="h-1.5 bg-gradient-to-r from-indigo-600 via-fuchsia-600 to-amber-500"></div>

        <div class="p-8 sm:p-10">

          {{-- Header --}}
          <div class="text-center">
            {{-- Ícono de advertencia --}}
            <div class="flex justify-center mb-5">
              <div class="relative">
                <div class="h-16 w-16 flex items-center justify-center rounded-2xl bg-yellow-50 text-yellow-700 ring-1 ring-yellow-200 shadow-sm">
                  <span class="material-symbols-outlined mi text-5xl leading-none">warning</span>
                </div>
                <span class="absolute -right-1 -top-1 inline-flex items-center rounded-full bg-amber-500 px-2 py-0.5 text-[10px] font-semibold text-white ring-1 ring-amber-400/60">
                  aviso
                </span>
              </div>
            </div>

            {{-- Título --}}
            <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-gray-900">
              Suscripción inactiva o vencida
            </h1>

            {{-- Mensaje --}}
            <p class="mt-3 text-sm sm:text-base leading-6 text-gray-600 max-w-prose mx-auto">
              Tu empresa actualmente no cuenta con una suscripción activa.
              Para continuar utilizando el sistema, es necesario renovar o activar tu plan.
            </p>

            @if(isset($empresa))
              <p class="mt-2 text-xs text-gray-500">
                Empresa: <span class="font-semibold">{{ $empresa->razon_social ?? ('#' . $empresa->id) }}</span>
              </p>
            @endif

            @if(isset($suscripcionActual) && $suscripcionActual)
              <p class="mt-1 text-xs text-gray-500">
                Última suscripción:
                <span class="font-semibold">{{ $suscripcionActual->plan }}</span>
                ({{ optional($suscripcionActual->fecha_inicio)->format('d/m/Y') }}
                 – {{ optional($suscripcionActual->fecha_vencimiento)->format('d/m/Y') }})
                <span class="ml-1 inline-flex items-center rounded-full px-2 py-0.5 text-[10px]
                    {{ $suscripcionActual->esta_vigente ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-red-50 text-red-700 border border-red-200' }}">
                  {{ $suscripcionActual->esta_vigente ? 'Activa' : 'Vencida' }}
                </span>
              </p>
            @endif
          </div>

          {{-- Mensajes de error --}}
          @if ($errors->any())
            <div class="mt-5 text-left mx-auto max-w-md">
              <div class="rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-700 flex items-start gap-2">
                <span class="material-symbols-outlined mi text-base mt-0.5">error</span>
                <div>{{ $errors->first() }}</div>
              </div>
            </div>
          @endif

          {{-- Planes --}}
          <div class="mt-8 grid grid-cols-1 md:grid-cols-3 gap-4">
            @foreach($plans as $key => $plan)
              <form method="POST" action="{{ route('billing.clip.checkout') }}"
                    class="bg-white/80 rounded-2xl border border-gray-200/70 px-4 py-5 flex flex-col shadow-sm hover:shadow-md transition">
                @csrf
                <input type="hidden" name="plan" value="{{ $key }}">

                <h2 class="text-sm font-semibold text-gray-900">
                  {{ $plan['label'] ?? ucfirst($key) }}
                </h2>

                <p class="mt-1 text-xs text-gray-500">
                  {{ $plan['months'] ?? 1 }} {{ ($plan['months'] ?? 1) > 1 ? 'meses' : 'mes' }}
                </p>

                <p class="mt-4 text-2xl font-bold text-indigo-600">
                  ${{ number_format($plan['amount'], 2) }}
                  <span class="text-xs text-gray-500">MXN</span>
                </p>

                <p class="mt-2 text-[11px] text-gray-500 flex-1">
                  Acceso completo al sistema durante el periodo seleccionado.
                </p>

                <button type="submit"
                        class="mt-4 inline-flex items-center justify-center h-10 w-full rounded-xl bg-indigo-600 text-white text-xs font-semibold shadow hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition">
                  <span class="material-symbols-outlined mi mr-1 text-sm">credit_card</span>
                  Pagar con Clip
                </button>
              </form>
            @endforeach
          </div>

          {{-- Botones extra --}}
          <div class="mt-8 flex flex-col sm:flex-row items-stretch sm:items-center justify-center gap-3">
            <a href="{{ route('home') }}"
               class="inline-flex items-center justify-center h-11 px-5 rounded-xl border border-gray-300 bg-white text-gray-800 text-sm font-medium hover:bg-gray-50 hover:border-gray-400 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-300 transition">
              <span class="material-symbols-outlined mi mr-2 text-base">arrow_back</span>
              Volver al inicio
            </a>
          </div>

          {{-- Nota informativa + tip --}}
          <div class="mt-6">
            <div class="mx-auto max-w-md rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-[13px] text-amber-800 flex items-start gap-2">
              <span class="material-symbols-outlined mi text-base mt-0.5">help</span>
              <div>
                Si ya realizaste el pago y aún no ves tu suscripción activa, espera unos minutos
                mientras se procesa la notificación de Clip.
                <div class="mt-1.5 text-amber-700/90">
                  Tip: Ten a la mano tu <span class="font-medium">número de pedido</span> y el <span class="font-medium">RFC</span> de tu empresa si necesitas soporte.
                </div>
              </div>
            </div>
          </div>

        </div>

      </div>
    </div>

  </div>
</x-app-layout>
