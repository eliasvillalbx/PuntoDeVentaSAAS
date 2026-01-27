{{-- resources/views/billing/alert.blade.php --}}

@php
  // Soporta ambos nombres por compatibilidad: $suscripcion (controlador) y $suscripcionActual (vista vieja)
  $sus = $suscripcionActual ?? $suscripcion ?? null;

  // Pequeño helper para meses
  $planMonths = function ($plan) {
      $m = (int) data_get($plan, 'months', 1);
      return max(1, $m);
  };

  // Helper de moneda (por plan o default)
  $planCurrency = function ($plan) {
      return strtoupper((string) (data_get($plan, 'currency') ?: config('stripe.currency', 'MXN')));
  };
@endphp

<x-app-layout>
  <div class="min-h-screen bg-gradient-to-br from-indigo-50 via-white to-purple-50 flex items-center justify-center py-16 px-6">

    {{-- Contenedor principal --}}
    <div class="w-full max-w-5xl relative">

      {{-- Glow decorativo --}}
      <div aria-hidden="true" class="absolute -inset-3 blur-2xl bg-gradient-to-tr from-indigo-200 via-fuchsia-200 to-amber-200 opacity-60 rounded-3xl"></div>

      {{-- Card --}}
      <div class="relative bg-white/90 backdrop-blur rounded-3xl shadow-xl ring-1 ring-gray-200/70 overflow-hidden">

        {{-- Top bar / acento --}}
        <div class="h-1.5 bg-gradient-to-r from-indigo-600 via-fuchsia-600 to-amber-500"></div>

        <div class="p-8 sm:p-10">

          {{-- Header --}}
          <div class="text-center">

            {{-- Ícono --}}
            <div class="flex justify-center mb-5">
              <div class="relative">
                <div class="h-16 w-16 flex items-center justify-center rounded-2xl bg-yellow-50 text-yellow-700 ring-1 ring-yellow-200 shadow-sm">
                  <span class="material-symbols-outlined text-5xl leading-none">warning</span>
                </div>
                <span class="absolute -right-1 -top-1 inline-flex items-center rounded-full bg-amber-500 px-2 py-0.5 text-[10px] font-semibold text-white ring-1 ring-amber-400/60">
                  aviso
                </span>
              </div>
            </div>

            <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-gray-900">
              Suscripción inactiva o vencida
            </h1>

            <p class="mt-3 text-sm sm:text-base leading-6 text-gray-600 max-w-prose mx-auto">
              Tu empresa no cuenta con una suscripción activa. Para continuar utilizando el sistema,
              selecciona un plan y completa el pago.
            </p>

            @if(isset($empresa))
              <p class="mt-2 text-xs text-gray-500">
                Empresa:
                <span class="font-semibold">{{ $empresa->razon_social ?? ('#' . $empresa->id) }}</span>
              </p>
            @endif

            @if($sus)
              @php
                $inicio = data_get($sus, 'fecha_inicio');
                $venc   = data_get($sus, 'fecha_vencimiento');

                // Si tu modelo trae una propiedad/atributo esta_vigente lo usamos; si no, lo inferimos.
                $vigente = data_get($sus, 'esta_vigente');
                if ($vigente === null && $venc) {
                    try { $vigente = now()->lte(\Illuminate\Support\Carbon::parse($venc)); } catch (\Throwable $e) { $vigente = null; }
                }
              @endphp

              <p class="mt-1 text-xs text-gray-500">
                Última suscripción:
                <span class="font-semibold">{{ data_get($sus, 'plan', '-') }}</span>

                @if($inicio || $venc)
                  (
                    {{ $inicio ? \Illuminate\Support\Carbon::parse($inicio)->format('d/m/Y') : '—' }}
                    –
                    {{ $venc ? \Illuminate\Support\Carbon::parse($venc)->format('d/m/Y') : '—' }}
                  )
                @endif

                @if($vigente !== null)
                  <span class="ml-1 inline-flex items-center rounded-full px-2 py-0.5 text-[10px]
                    {{ $vigente ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-red-50 text-red-700 border border-red-200' }}">
                    {{ $vigente ? 'Activa' : 'Vencida' }}
                  </span>
                @endif
              </p>
            @endif
          </div>

          {{-- Mensaje de éxito / status --}}
          @if (session('status'))
            <div class="mt-5 text-left mx-auto max-w-2xl">
              <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800 flex items-start gap-2">
                <span class="material-symbols-outlined text-base mt-0.5">check_circle</span>
                <div>{{ session('status') }}</div>
              </div>
            </div>
          @endif

          {{-- Mensajes de error --}}
          @if ($errors->any())
            <div class="mt-5 text-left mx-auto max-w-2xl">
              <div class="rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-700 flex items-start gap-2">
                <span class="material-symbols-outlined text-base mt-0.5">error</span>
                <div>{{ $errors->first() }}</div>
              </div>
            </div>
          @endif

          {{-- Badge de referencia (debug útil) --}}
          @if (session('stripe_me_reference_id') || session('clip_me_reference_id'))
            <div class="mt-4 text-center">
              <span class="inline-flex items-center gap-2 rounded-full border border-gray-200 bg-white px-3 py-1 text-[11px] text-gray-600">
                <span class="material-symbols-outlined text-sm">receipt_long</span>
                Referencia:
                <span class="font-semibold">
                  {{ session('stripe_me_reference_id') ?? session('clip_me_reference_id') }}
                </span>
              </span>
            </div>
          @endif

          {{-- Sección: método recomendado --}}
          <div class="mt-8 mx-auto max-w-4xl">
            <div class="rounded-2xl border border-indigo-200 bg-indigo-50/70 px-4 py-3 text-sm text-indigo-900 flex items-start gap-2">
              <span class="material-symbols-outlined text-base mt-0.5">info</span>
              <div>
                <div class="font-semibold">Método recomendado: Stripe</div>
                <div class="mt-0.5 text-indigo-800/90 text-[13px]">
                  El pago se realiza en un checkout seguro y al confirmarse se activará tu suscripción automáticamente.
                  Si al terminar no ves el cambio, espera unos minutos (webhook).
                </div>
              </div>
            </div>
          </div>

          {{-- Planes --}}
          <div class="mt-8 grid grid-cols-1 md:grid-cols-3 gap-4">
            @forelse($plans as $key => $plan)
              @php
                $months   = $planMonths($plan);
                $currency = $planCurrency($plan);
                $label    = data_get($plan, 'label') ?? ucfirst((string) $key);
                $amount   = (float) data_get($plan, 'amount', 0);
              @endphp

              <div class="bg-white/80 rounded-2xl border border-gray-200/70 px-5 py-6 shadow-sm hover:shadow-md transition flex flex-col">
                <div class="flex items-start justify-between gap-3">
                  <div>
                    <h2 class="text-sm font-semibold text-gray-900">{{ $label }}</h2>
                    <p class="mt-1 text-xs text-gray-500">
                      {{ $months }} {{ $months > 1 ? 'meses' : 'mes' }}
                    </p>
                  </div>

                  <span class="inline-flex items-center rounded-full bg-gray-50 text-gray-700 border border-gray-200 px-2 py-0.5 text-[10px] font-semibold">
                    {{ $key }}
                  </span>
                </div>

                <div class="mt-4">
                  <p class="text-3xl font-bold text-indigo-600">
                    ${{ number_format($amount, 2) }}
                    <span class="text-xs text-gray-500">{{ $currency }}</span>
                  </p>
                  <p class="mt-1 text-[11px] text-gray-500">
                    Acceso completo al sistema durante el periodo seleccionado.
                  </p>
                </div>

                {{-- “Incluye” --}}
                <ul class="mt-4 space-y-2 text-[12px] text-gray-600 flex-1">
                  <li class="flex items-start gap-2">
                    <span class="material-symbols-outlined text-base text-emerald-600">check</span>
                    Soporte y actualizaciones durante tu plan
                  </li>
                  <li class="flex items-start gap-2">
                    <span class="material-symbols-outlined text-base text-emerald-600">check</span>
                    Acceso a módulos y reportes habilitados
                  </li>
                  <li class="flex items-start gap-2">
                    <span class="material-symbols-outlined text-base text-emerald-600">check</span>
                    Renovación rápida desde esta misma pantalla
                  </li>
                </ul>

                {{-- Botones de pago (sin forms anidados) --}}
                <div class="mt-5 space-y-2">

                  {{-- STRIPE (principal) --}}
                  <form method="POST" action="{{ route('billing.stripe.checkout') }}">
                    @csrf
                    <input type="hidden" name="plan" value="{{ $key }}">
                    <button type="submit"
                      class="inline-flex items-center justify-center h-10 w-full rounded-xl bg-indigo-600 text-white text-xs font-semibold shadow hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition">
                      <span class="material-symbols-outlined mr-1 text-sm">lock</span>
                      Pagar con Stripe
                    </button>
                  </form>

                  {{-- CLIP (secundario / preservado) --}}
                  <form method="POST" action="{{ route('billing.clip.checkout') }}">
                    @csrf
                    <input type="hidden" name="plan" value="{{ $key }}">
                    <button type="submit"
                      class="inline-flex items-center justify-center h-10 w-full rounded-xl border border-gray-300 bg-white text-gray-800 text-xs font-semibold hover:bg-gray-50 hover:border-gray-400 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-300 transition">
                      <span class="material-symbols-outlined mr-1 text-sm">credit_card</span>
                      Pagar con Clip
                    </button>
                  </form>

                  <p class="text-[11px] text-gray-500 text-center">
                    Si un método falla, intenta con el otro.
                  </p>
                </div>
              </div>
            @empty
              <div class="md:col-span-3">
                <div class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700 flex items-start gap-2">
                  <span class="material-symbols-outlined text-base mt-0.5">error</span>
                  <div>
                    No hay planes configurados. Revisa <span class="font-semibold">config('clip.plans')</span> o la configuración de planes.
                  </div>
                </div>
              </div>
            @endforelse
          </div>

          {{-- Botones extra --}}
          <div class="mt-8 flex flex-col sm:flex-row items-stretch sm:items-center justify-center gap-3">
            <a href="{{ route('home') }}"
              class="inline-flex items-center justify-center h-11 px-5 rounded-xl border border-gray-300 bg-white text-gray-800 text-sm font-medium hover:bg-gray-50 hover:border-gray-400 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-300 transition">
              <span class="material-symbols-outlined mr-2 text-base">arrow_back</span>
              Volver al inicio
            </a>

            <a href="{{ route('dashboard') }}"
              class="inline-flex items-center justify-center h-11 px-5 rounded-xl bg-gray-900 text-white text-sm font-medium hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-400 transition">
              <span class="material-symbols-outlined mr-2 text-base">space_dashboard</span>
              Ir al Dashboard
            </a>
          </div>

          {{-- Nota informativa --}}
          <div class="mt-6">
            <div class="mx-auto max-w-3xl rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-[13px] text-amber-900 flex items-start gap-2">
              <span class="material-symbols-outlined text-base mt-0.5">help</span>
              <div>
                Si ya realizaste el pago y aún no ves tu suscripción activa, espera unos minutos mientras se procesa la notificación (webhook).
                <div class="mt-1.5 text-amber-800/90">
                  Tip: Ten a la mano tu <span class="font-medium">referencia</span> y el <span class="font-medium">RFC</span> de tu empresa si necesitas soporte.
                </div>
              </div>
            </div>
          </div>

        </div>
      </div>

      {{-- Footer mini --}}
      <div class="mt-4 text-center text-[11px] text-gray-500">
        Si tienes problemas recurrentes para pagar, contacta a TI/Soporte para revisar configuración de la pasarela.
      </div>
    </div>
  </div>
</x-app-layout>
