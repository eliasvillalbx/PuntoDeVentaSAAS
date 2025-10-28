<x-app-layout>
  <div class="min-h-screen flex flex-col items-center justify-center bg-gray-100 py-12 px-6">

    {{-- Contenedor principal --}}
    <div class="w-full max-w-lg bg-white rounded-2xl shadow-lg p-8 text-center border border-gray-200">

      {{-- Ícono de advertencia --}}
      <div class="flex justify-center mb-4">
        <div class="h-16 w-16 flex items-center justify-center rounded-full bg-yellow-100 text-yellow-600">
          <span class="material-icons text-5xl">warning</span>
        </div>
      </div>

      {{-- Título --}}
      <h1 class="text-2xl font-bold text-gray-800 mb-3">
        Suscripción inactiva o vencida
      </h1>

      {{-- Mensaje --}}
      <p class="text-gray-600 mb-6">
        Tu empresa actualmente no cuenta con una suscripción activa.  
        Para continuar utilizando el sistema, es necesario renovar o activar tu plan.
      </p>

      {{-- Mensajes de error --}}
      @if ($errors->any())
        <div class="mb-4 text-red-700 bg-red-50 border border-red-200 rounded-lg p-3 text-sm">
          {{ $errors->first() }}
        </div>
      @endif

      {{-- Botones de acción --}}
      <div class="flex flex-col sm:flex-row justify-center gap-3">
        <a href="{{ route('home') }}"
           class="inline-flex items-center justify-center px-5 py-2.5 bg-gray-200 text-gray-800 font-medium rounded-lg hover:bg-gray-300 transition">
          <span class="material-icons mr-1 text-sm">arrow_back</span> Volver al inicio
        </a>

        <form method="POST" action="{{ route('suscripciones.store') }}">
          @csrf
          <button type="submit"
                  class="inline-flex items-center justify-center px-5 py-2.5 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 transition">
            <span class="material-icons mr-1 text-sm">autorenew</span> Activar suscripción
          </button>
        </form>
      </div>

      {{-- Nota informativa --}}
      <div class="mt-8 text-sm text-gray-500">
        Si ya realizaste el pago, contacta al soporte para validar tu suscripción.
      </div>
    </div>
  </div>
</x-app-layout>
