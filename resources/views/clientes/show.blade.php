<x-app-layout>
  <x-slot name="header">
    <div class="flex items-center justify-between">
      <h1 class="text-xl sm:text-2xl font-semibold text-gray-900 flex items-center gap-2">
        <span class="material-symbols-outlined mi">person</span>
        Cliente #{{ $cliente->id }}
      </h1>
      <div class="flex gap-2">
        <a href="{{ route('clientes.edit', $cliente) }}" class="h-10 px-4 rounded-lg bg-gray-900 text-white text-sm font-medium hover:bg-black">Editar</a>
      </div>
    </div>
  </x-slot>

  <div class="max-w-4xl mx-auto py-6 px-4 sm:px-6 lg:px-8 space-y-4">
    @if (session('status'))
      <div class="p-3 rounded-lg bg-green-50 text-green-700 text-sm">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
      <div class="p-3 rounded-lg bg-red-50 text-red-700 text-sm">
        <ul class="list-disc pl-6">
          @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
        </ul>
      </div>
    @endif

    <div class="bg-white border rounded-xl shadow-sm p-4 grid grid-cols-1 md:grid-cols-2 gap-4">
      <div>
        <div class="text-xs text-gray-500">Tipo de persona</div>
        <div class="font-medium capitalize">{{ $cliente->tipo_persona }}</div>
      </div>
      <div>
        <div class="text-xs text-gray-500">Activo</div>
        <div>
          <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium {{ $cliente->activo ? 'bg-green-100 text-green-700' : 'bg-gray-200 text-gray-700' }}">
            {{ $cliente->activo ? 'Sí' : 'No' }}
          </span>
        </div>
      </div>
      <div class="md:col-span-2">
        <div class="text-xs text-gray-500">Nombre / Razón social</div>
        <div class="font-medium">{{ $cliente->nombre_mostrar }}</div>
      </div>
      <div>
        <div class="text-xs text-gray-500">RFC</div>
        <div class="font-medium">{{ $cliente->rfc ?? '—' }}</div>
      </div>
      <div>
        <div class="text-xs text-gray-500">Email</div>
        <div class="font-medium">{{ $cliente->email ?? '—' }}</div>
      </div>
      <div>
        <div class="text-xs text-gray-500">Teléfono</div>
        <div class="font-medium">{{ $cliente->telefono ?? '—' }}</div>
      </div>
      <div class="md:col-span-2">
        <div class="text-xs text-gray-500">Dirección</div>
        <div class="font-medium">
          {{ $cliente->calle ?? '' }} {{ $cliente->numero_ext ?? '' }} {{ $cliente->numero_int ? 'Int. '.$cliente->numero_int : '' }},
          {{ $cliente->colonia ?? '' }}, {{ $cliente->municipio ?? '' }}, {{ $cliente->estado ?? '' }} {{ $cliente->cp ?? '' }}
        </div>
      </div>
    </div>

    <div class="bg-white border rounded-xl shadow-sm p-4">
      <div class="text-sm text-gray-600">Ventas asociadas: <span class="font-semibold">{{ $ventasCount }}</span></div>
    </div>

    <div class="flex gap-2">
      <a href="{{ route('clientes.index') }}" class="h-10 px-4 rounded-lg bg-gray-100 text-gray-700 text-sm font-medium hover:bg-gray-200">Volver</a>
      <form action="{{ route('clientes.destroy', $cliente) }}" method="POST" onsubmit="return confirm('¿Eliminar cliente?');">
        @csrf @method('DELETE')
        <button class="h-10 px-4 rounded-lg bg-red-50 text-red-700 text-sm font-medium hover:bg-red-100">Eliminar</button>
      </form>
    </div>
  </div>
</x-app-layout>
