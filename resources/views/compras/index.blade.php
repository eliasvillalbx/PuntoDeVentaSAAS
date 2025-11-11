<x-app-layout>
  <x-slot name="header">
    <div class="flex items-center justify-between">
      <h1 class="text-xl sm:text-2xl font-semibold text-gray-900 flex items-center gap-2">
        <span class="material-symbols-outlined mi">local_mall</span>
        Compras / Abastecimiento
      </h1>
      <a href="{{ route('compras.create') }}"
         class="inline-flex items-center gap-2 h-10 px-4 rounded-xl bg-cyan-600 text-white hover:bg-cyan-700">
        <span class="material-symbols-outlined mi text-base">add</span>
        Nueva compra
      </a>
    </div>
  </x-slot>

  <div class="max-w-7xl mx-auto space-y-6">
    @if ($errors->any())
      <div class="rounded-lg bg-red-50 text-red-800 ring-1 ring-red-200 p-3">
        {{ $errors->first() }}
      </div>
    @endif
    @if (session('success'))
      <div class="rounded-lg bg-green-50 text-green-800 ring-1 ring-green-200 p-3">{{ session('success') }}</div>
    @endif

    {{-- KPIs --}}
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
      <div class="p-4 bg-white rounded-xl border"><div class="text-xs text-gray-500">Hoy</div><div class="text-xl font-bold">${{ number_format($kpis['compras_hoy'] ?? 0,2) }}</div></div>
      <div class="p-4 bg-white rounded-xl border"><div class="text-xs text-gray-500">Este mes</div><div class="text-xl font-bold">${{ number_format($kpis['compras_mes'] ?? 0,2) }}</div></div>
      <div class="p-4 bg-white rounded-xl border"><div class="text-xs text-gray-500">Compras</div><div class="text-xl font-bold">{{ $kpis['conteo'] ?? 0 }}</div></div>
      <div class="p-4 bg-white rounded-xl border"><div class="text-xs text-gray-500">Órdenes pendientes</div><div class="text-xl font-bold">{{ $kpis['pendientes'] ?? 0 }}</div></div>
    </div>

    {{-- Filtros --}}
    <form method="GET" class="bg-white rounded-xl border p-4 grid grid-cols-1 sm:grid-cols-5 gap-3">
      <input type="date" name="fecha_inicio" value="{{ request('fecha_inicio') }}" class="rounded-lg border-gray-300">
      <input type="date" name="fecha_fin" value="{{ request('fecha_fin') }}" class="rounded-lg border-gray-300">
      <select name="estatus" class="rounded-lg border-gray-300">
        <option value="">Estatus</option>
        @foreach (['borrador','orden_compra','recibida','cancelada'] as $st)
          <option value="{{ $st }}" @selected(request('estatus')===$st)>{{ ucfirst(str_replace('_',' ', $st)) }}</option>
        @endforeach
      </select>
      <select name="id_proveedor" class="rounded-lg border-gray-300">
        <option value="">Proveedor</option>
        @foreach ($proveedores as $p)
          <option value="{{ $p->id }}" @selected(request('id_proveedor')==$p->id)>{{ $p->nombre }}</option>
        @endforeach
      </select>
      <div class="flex">
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Buscar…" class="flex-1 rounded-l-lg border-gray-300">
        <button class="px-3 rounded-r-lg bg-gray-800 text-white">Filtrar</button>
      </div>
    </form>

    {{-- Tabla --}}
    <div class="bg-white rounded-xl border overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="bg-gray-50 text-gray-700">
          <tr>
            <th class="px-4 py-3 text-left">#</th>
            <th class="px-4 py-3 text-left">Fecha</th>
            <th class="px-4 py-3 text-left">Proveedor</th>
            <th class="px-4 py-3 text-left">Estatus</th>
            <th class="px-4 py-3 text-right">Total</th>
            <th class="px-4 py-3 text-right">Acciones</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          @forelse ($compras as $c)
            <tr class="hover:bg-gray-50">
              <td class="px-4 py-3">{{ $c->id }}</td>
              <td class="px-4 py-3">{{ \Illuminate\Support\Carbon::parse($c->fecha_compra)->format('d/m/Y') }}</td>
              <td class="px-4 py-3">{{ $c->proveedor }}</td>
              <td class="px-4 py-3">
                @php
                  $badge = [
                    'borrador'=>'bg-gray-100 text-gray-700',
                    'orden_compra'=>'bg-blue-100 text-blue-700',
                    'recibida'=>'bg-green-100 text-green-700',
                    'cancelada'=>'bg-red-100 text-red-700',
                  ][$c->estatus] ?? 'bg-gray-100 text-gray-700';
                @endphp
                <span class="inline-flex items-center text-xs px-2 py-0.5 rounded-full {{ $badge }}">
                  {{ ucfirst(str_replace('_',' ', $c->estatus)) }}
                </span>
              </td>
              <td class="px-4 py-3 text-right">${{ number_format($c->total,2) }}</td>
              <td class="px-4 py-3">
                <div class="flex justify-end gap-2">
                  <a href="{{ route('compras.show', $c->id) }}" class="text-cyan-700 hover:underline">Ver</a>
                  <a href="{{ route('compras.edit', $c->id) }}" class="text-gray-700 hover:underline">Editar</a>
                  <form method="POST" action="{{ route('compras.destroy', $c->id) }}" onsubmit="return confirm('¿Eliminar compra?')">
                    @csrf @method('DELETE')
                    <button class="text-red-700 hover:underline">Eliminar</button>
                  </form>
                </div>
              </td>
            </tr>
          @empty
            <tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">No hay compras registradas.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div>{{ $compras->links() }}</div>
  </div>
</x-app-layout>
