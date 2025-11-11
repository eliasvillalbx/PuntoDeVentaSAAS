<x-app-layout>
  <x-slot name="header">
    <div class="flex items-center justify-between">
      <h1 class="text-xl sm:text-2xl font-semibold text-gray-900 flex items-center gap-2">
        <span class="material-symbols-outlined mi">receipt_long</span>
        Compra #{{ $compra->id }}
      </h1>
      <div class="flex items-center gap-2">
        @if(in_array($compra->estatus, ['borrador','orden_compra']))
          <form method="POST" action="{{ route('compras.recibir', $compra->id) }}">
            @csrf
            <button class="inline-flex items-center gap-2 h-10 px-4 rounded-xl bg-green-600 text-white hover:bg-green-700">
              <span class="material-symbols-outlined mi text-base">inventory_2</span>
              Marcar como recibida
            </button>
          </form>
        @endif
        <a href="{{ route('compras.edit', $compra->id) }}"
           class="inline-flex items-center gap-2 h-10 px-4 rounded-xl border border-gray-300 text-gray-700 text-sm hover:bg-gray-50">
          <span class="material-symbols-outlined mi text-base">edit</span>
          Editar
        </a>
        <a href="{{ route('compras.index') }}"
           class="inline-flex items-center gap-2 h-10 px-4 rounded-xl border border-gray-300 text-gray-700 text-sm hover:bg-gray-50">
          <span class="material-symbols-outlined mi text-base">arrow_back</span>
          Volver
        </a>
      </div>
    </div>
  </x-slot>

  <div class="max-w-6xl mx-auto space-y-6">
    @if ($errors->any())
      <div class="rounded-lg bg-red-50 text-red-800 ring-1 ring-red-200 p-3">{{ $errors->first() }}</div>
    @endif
    @if (session('success'))
      <div class="rounded-lg bg-green-50 text-green-800 ring-1 ring-green-200 p-3">{{ session('success') }}</div>
    @endif

    <div class="bg-white rounded-xl border p-6 grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
      <div><div class="text-gray-500">Fecha</div><div class="font-medium">{{ \Illuminate\Support\Carbon::parse($compra->fecha_compra)->format('d/m/Y') }}</div></div>
      <div><div class="text-gray-500">Proveedor</div><div class="font-medium">{{ $compra->proveedor }}</div></div>
      <div><div class="text-gray-500">Estatus</div>
        @php
          $badge = [
            'borrador'=>'bg-gray-100 text-gray-700',
            'orden_compra'=>'bg-blue-100 text-blue-700',
            'recibida'=>'bg-green-100 text-green-700',
            'cancelada'=>'bg-red-100 text-red-700',
          ][$compra->estatus] ?? 'bg-gray-100 text-gray-700';
        @endphp
        <span class="inline-flex items-center text-xs px-2 py-0.5 rounded-full {{ $badge }}">
          {{ ucfirst(str_replace('_',' ', $compra->estatus)) }}
        </span>
      </div>
      <div><div class="text-gray-500">Total</div><div class="font-bold">${{ number_format($compra->total,2) }}</div></div>
      <div class="sm:col-span-2">
        <div class="text-gray-500">Observaciones</div>
        <div>{{ $compra->observaciones ?: '—' }}</div>
      </div>
    </div>

    <div class="bg-white rounded-xl border overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="bg-gray-50 text-gray-700">
          <tr>
            <th class="px-4 py-3 text-left">SKU</th>
            <th class="px-4 py-3 text-left">Producto</th>
            <th class="px-4 py-3 text-right">Cantidad</th>
            <th class="px-4 py-3 text-right">Costo</th>
            <th class="px-4 py-3 text-right">Descuento</th>
            <th class="px-4 py-3 text-right">Total línea</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          @foreach ($detalles as $d)
            <tr>
              <td class="px-4 py-3">{{ $d->sku }}</td>
              <td class="px-4 py-3">{{ $d->producto }}</td>
              <td class="px-4 py-3 text-right">{{ number_format($d->cantidad,2) }}</td>
              <td class="px-4 py-3 text-right">${{ number_format($d->costo_unitario,2) }}</td>
              <td class="px-4 py-3 text-right">${{ number_format($d->descuento ?? 0,2) }}</td>
              <td class="px-4 py-3 text-right">${{ number_format($d->total_linea,2) }}</td>
            </tr>
          @endforeach
        </tbody>
        <tfoot class="bg-gray-50">
          <tr>
            <td colspan="4"></td>
            <td class="px-4 py-3 text-right font-medium">Subtotal</td>
            <td class="px-4 py-3 text-right">${{ number_format($compra->subtotal,2) }}</td>
          </tr>
          <tr>
            <td colspan="4"></td>
            <td class="px-4 py-3 text-right font-medium">IVA (16%)</td>
            <td class="px-4 py-3 text-right">${{ number_format($compra->iva,2) }}</td>
          </tr>
          <tr>
            <td colspan="4"></td>
            <td class="px-4 py-3 text-right font-bold">Total</td>
            <td class="px-4 py-3 text-right font-bold">${{ number_format($compra->total,2) }}</td>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>
</x-app-layout>
