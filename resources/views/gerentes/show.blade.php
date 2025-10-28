<x-app-layout>
  {{-- Header --}}
  <x-slot name="header">
    <div class="flex items-center justify-between">
      <h1 class="text-xl sm:text-2xl font-semibold text-gray-900 flex items-center gap-2">
        <span class="material-symbols-outlined mi">visibility</span>
        Detalle de gerente
      </h1>
      <div class="flex items-center gap-2">
        <a href="{{ route('gerentes.index') }}"
           class="inline-flex items-center gap-2 h-10 px-4 rounded-xl border border-gray-300 text-gray-700 text-sm hover:bg-gray-50">
          <span class="material-symbols-outlined mi text-base">arrow_back</span>
          Volver
        </a>
        <a href="{{ route('gerentes.edit', $gerente) }}"
           class="inline-flex items-center gap-2 h-10 px-4 rounded-xl bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700">
          <span class="material-symbols-outlined mi text-base">edit</span>
          Editar
        </a>
      </div>
    </div>
  </x-slot>

  <div class="max-w-5xl mx-auto">
    @if (session('success'))
      <div class="mb-4 rounded-lg bg-green-50 text-green-800 ring-1 ring-green-200 p-3">{{ session('success') }}</div>
    @endif
    @if (session('error'))
      <div class="mb-4 rounded-lg bg-red-50 text-red-800 ring-1 ring-red-200 p-3">{{ session('error') }}</div>
    @endif

    <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-6">
      {{-- Info principal --}}
      <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <div>
          <h3 class="text-xs text-gray-500 mb-1">Nombre</h3>
          <p class="text-sm text-gray-900 font-medium">
            {{ $gerente->nombre_completo ?: trim("{$gerente->nombre} {$gerente->apellido_paterno} {$gerente->apellido_materno}") }}
          </p>
        </div>
        <div>
          <h3 class="text-xs text-gray-500 mb-1">Email</h3>
          <p class="text-sm text-gray-900 font-medium">{{ $gerente->email }}</p>
        </div>
        <div>
          <h3 class="text-xs text-gray-500 mb-1">Teléfono</h3>
          <p class="text-sm text-gray-900 font-medium">{{ $gerente->telefono ?? '—' }}</p>
        </div>
        <div>
          <h3 class="text-xs text-gray-500 mb-1">Empresa</h3>
          <p class="text-sm text-gray-900 font-medium">{{ $gerente->empresa?->razon_social ?? '—' }}</p>
        </div>
        <div>
          <h3 class="text-xs text-gray-500 mb-1">Creado</h3>
          <p class="text-sm text-gray-900 font-medium">{{ $gerente->created_at?->format('Y-m-d H:i') }}</p>
        </div>
        <div>
          <h3 class="text-xs text-gray-500 mb-1">Actualizado</h3>
          <p class="text-sm text-gray-900 font-medium">{{ $gerente->updated_at?->format('Y-m-d H:i') }}</p>
        </div>
      </section>

      {{-- Acciones peligrosas --}}
      <section class="pt-4 border-t border-gray-100">
        <form method="POST" action="{{ route('gerentes.destroy', $gerente) }}"
              onsubmit="return confirm('¿Eliminar este gerente? Esta acción no se puede deshacer.');">
          @csrf @method('DELETE')
          <button type="submit" class="inline-flex items-center gap-2 h-10 px-4 rounded-xl bg-red-600 text-white text-sm font-medium hover:bg-red-700">
            <span class="material-symbols-outlined mi text-base">delete</span>
            Eliminar gerente
          </button>
        </form>
      </section>
    </div>
  </div>
</x-app-layout>
