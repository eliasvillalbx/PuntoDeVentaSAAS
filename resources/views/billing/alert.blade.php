<x-app-layout>
  <div class="min-h-screen bg-gradient-to-br from-indigo-50 via-white to-purple-50 flex items-center justify-center py-16 px-6">

    {{-- Contenedor principal --}}
    <div class="w-full max-w-xl relative">
      {{-- Glow decorativo --}}
      <div aria-hidden="true" class="absolute -inset-3 blur-2xl bg-gradient-to-tr from-indigo-200 via-fuchsia-200 to-amber-200 opacity-60 rounded-3xl"></div>

      {{-- Card --}}
      <div class="relative bg-white/90 backdrop-blur rounded-3xl shadow-xl ring-1 ring-gray-200/70 overflow-hidden">

        {{-- Top bar / acento --}}
        <div class="h-1.5 bg-gradient-to-r from-indigo-600 via-fuchsia-600 to-amber-500"></div>

        <div class="p-8 sm:p-10 text-center">

          {{-- Ícono de advertencia (material-symbols-outlined mi) --}}
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

          {{-- Mensajes de error --}}
          @if ($errors->any())
            <div class="mt-5 text-left mx-auto max-w-sm">
              <div class="rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-700 flex items-start gap-2">
                <span class="material-symbols-outlined mi text-base mt-0.5">error</span>
                <div>{{ $errors->first() }}</div>
              </div>
            </div>
          @endif

          {{-- Botones de acción --}}
          <div class="mt-7 flex flex-col sm:flex-row items-stretch sm:items-center justify-center gap-3">
            <a href="{{ route('home') }}"
               class="inline-flex items-center justify-center h-11 px-5 rounded-xl border border-gray-300 bg-white text-gray-800 text-sm font-medium hover:bg-gray-50 hover:border-gray-400 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-300 transition">
              <span class="material-symbols-outlined mi mr-2 text-base">arrow_back</span>
              Volver al inicio
            </a>

            <form method="POST" action="{{ route('suscripciones.store') }}" class="contents">
              @csrf
              <button type="submit"
                      class="inline-flex items-center justify-center h-11 px-5 rounded-xl bg-indigo-600 text-white text-sm font-semibold shadow hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition">
                <span class="material-symbols-outlined mi mr-2 text-base">autorenew</span>
                Activar suscripción
              </button>
            </form>
          </div>

          {{-- Nota informativa + tip --}}
          <div class="mt-8">
            <div class="mx-auto max-w-md rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-[13px] text-amber-800 flex items-start gap-2">
              <span class="material-symbols-outlined mi text-base mt-0.5">help</span>
              <div>
                Si ya realizaste el pago, contacta al soporte para validar tu suscripción.
                <div class="mt-1.5 text-amber-700/90">
                  Tip: Ten a la mano tu <span class="font-medium">número de pedido</span> y el <span class="font-medium">RFC</span> de tu empresa.
                </div>
              </div>
            </div>
          </div>

        </div>

      </div>
    </div>

  </div>
</x-app-layout>
