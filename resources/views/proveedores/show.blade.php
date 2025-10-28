<x-app-layout>
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
           class="inline-flex items-center gap-2 h-10 px-4 rounded-xl border border-gray-300 text-gray-700 text-sm hover:bg-gray-50">
          <span class="material-symbols-outlined mi text-base">arrow_back</span>
          Volver
        </a>
      </div>
    </div>
  </x-slot>

  <div class="max-w-4xl mx-auto space-y-6">
    @if (session('success'))
      <div class="rounded-lg bg-green-50 text-green-800 ring-1 ring-green-200 p-3">{{ session('success') }}</div>
    @endif
    @if (session('error'))
      <div class="rounded-lg bg-red-50 text-red-800 ring-1 ring-red-200 p-3">{{ session('error') }}</div>
    @endif

    <div class="bg-white rounded-xl border border-gray-200 p-6">
      <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
        <div>
          <dt class="text-gray-500">RFC</dt>
          <dd class="text-gray-900 font-medium">{{ $proveedor->rfc ?: '—' }}</dd>
        </div>
        <div>
          <dt class="text-gray-500">Contacto</dt>
          <dd class="text-gray-900">{{ $proveedor->contacto ?: '—' }}</dd>
        </div>
        <div>
          <dt class="text-gray-500">Email</dt>
          <dd class="text-gray-900">{{ $proveedor->email ?: '—' }}</dd>
        </div>
        <div>
          <dt class="text-gray-500">Teléfono</dt>
          <dd class="text-gray-900">{{ $proveedor->telefono ?: '—' }}</dd>
        </div>
        <div>
          <dt class="text-gray-500">Estado</dt>
          <dd>
            <span class="inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full
              {{ $proveedor->activo ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
              <span class="material-symbols-outlined mi text-sm">{{ $proveedor->activo ? 'check' : 'block' }}</span>
              {{ $proveedor->activo ? 'Activo' : 'Inactivo' }}
            </span>
          </dd>
        </div>
      </dl>
    </div>
  </div>
</x-app-layout>
