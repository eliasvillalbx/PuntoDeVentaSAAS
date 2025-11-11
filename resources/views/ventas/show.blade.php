<x-app-layout>
  <x-slot name="header">
    <div class="flex items-center justify-between">
      <h1 class="text-xl sm:text-2xl font-semibold text-gray-900 flex items-center gap-2">
        <span class="material-symbols-outlined mi">receipt_long</span>
        Venta #{{ $venta->id }}
      </h1>

      {{-- Acciones superiores --}}
      <div class="flex items-center gap-2 print:hidden">
        @if(in_array($venta->estatus, ['borrador','prefactura']))
          <form method="POST"
                action="{{ route('ventas.convertirPrefactura', $venta) }}"
                onsubmit="return confirm('¿Convertir a venta facturada y descontar stock?');">
            @csrf
            <button class="inline-flex items-center gap-2 h-10 px-4 rounded-xl bg-green-600 text-white text-sm font-medium hover:bg-green-700">
              <span class="material-symbols-outlined mi text-base">task_alt</span>
              Confirmar venta
            </button>
          </form>
        @endif

        {{-- Descargar PDF --}}
        <a href="{{ route('ventas.pdf', $venta) }}"
           target="_blank" rel="noopener"
           class="inline-flex items-center gap-2 h-10 px-4 rounded-xl border border-gray-300 text-gray-700 text-sm hover:bg-gray-50">
          <span class="material-symbols-outlined mi text-base">picture_as_pdf</span>
          PDF
        </a>

        {{-- Imprimir (navegador) --}}
        <button type="button" onclick="window.print()"
                class="inline-flex items-center gap-2 h-10 px-4 rounded-xl border border-gray-300 text-gray-700 text-sm hover:bg-gray-50">
          <span class="material-symbols-outlined mi text-base">print</span>
          Imprimir
        </button>

        @if($venta->estatus !== 'cancelada')
          <a href="{{ route('ventas.edit', $venta) }}"
             class="inline-flex items-center gap-2 h-10 px-4 rounded-xl border border-gray-300 text-gray-700 text-sm hover:bg-gray-50">
            <span class="material-symbols-outlined mi text-base">edit</span>
            Editar
          </a>
        @endif

        <a href="{{ route('ventas.index', ['empresa_id' => $venta->empresa_id]) }}"
           class="inline-flex items-center gap-2 h-10 px-4 rounded-xl border border-gray-300 text-gray-700 text-sm hover:bg-gray-50">
          <span class="material-symbols-outlined mi text-base">arrow_back</span>
          Volver
        </a>
      </div>
    </div>
  </x-slot>

  <div class="max-w-6xl mx-auto space-y-6">
    {{-- Mensajes --}}
    @if (session('status'))
      <div class="rounded-lg bg-green-50 text-green-800 ring-1 ring-green-200 p-3">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
      <div class="rounded-lg bg-red-50 text-red-800 ring-1 ring-red-200 p-3">{{ $errors->first() }}</div>
    @endif

    {{-- Encabezado de venta --}}
    <div class="bg-white rounded-xl border border-gray-200 p-6">
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div>
          <div class="text-xs text-gray-500">Empresa</div>
          <div class="font-medium">
            {{ $venta->empresa?->nombre_comercial ?? $venta->empresa?->razon_social ?? ('Empresa #'.$venta->empresa_id) }}
          </div>
        </div>
        <div>
          <div class="text-xs text-gray-500">Fecha</div>
          <div class="font-medium">
            {{ \Illuminate\Support\Carbon::parse($venta->fecha_venta)->format('d/m/Y') }}
          </div>
        </div>
        <div>
          <div class="text-xs text-gray-500">Vendedor</div>
          <div class="font-medium">
            {{ $venta->usuario?->nombre }} {{ $venta->usuario?->apellido_paterno }}
          </div>
        </div>

        <div>
          <div class="text-xs text-gray-500">Cliente</div>
          <div class="font-medium">
            {{ $venta->cliente?->nombre ?? $venta->cliente?->razon_social ?? 'Venta directa' }}
          </div>
        </div>
        <div>
          <div class="text-xs text-gray-500">Estatus</div>
          @php
            $badge = [
              'borrador'   => 'bg-gray-100 text-gray-700 ring-gray-200',
              'prefactura' => 'bg-amber-100 text-amber-800 ring-amber-200',
              'facturada'  => 'bg-green-100 text-green-800 ring-green-200',
              'cancelada'  => 'bg-red-100 text-red-800 ring-red-200',
            ][$venta->estatus] ?? 'bg-gray-100 text-gray-700 ring-gray-200';
          @endphp
          <span class="inline-flex items-center px-2 h-6 rounded-full text-xs font-semibold ring-1 {{ $badge }}">
            {{ ucfirst($venta->estatus) }}
          </span>
        </div>
        <div>
          <div class="text-xs text-gray-500">ID</div>
          <div class="font-medium">#{{ $venta->id }}</div>
        </div>
      </div>

      @if($venta->observaciones)
        <div class="mt-4">
          <div class="text-xs text-gray-500 mb-1">Observaciones</div>
          <div class="text-sm text-gray-800 whitespace-pre-line">{{ $venta->observaciones }}</div>
        </div>
      @endif
    </div>

    {{-- Productos --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-3 py-2 text-left font-semibold text-gray-700">Producto</th>
              <th class="px-3 py-2 text-right font-semibold text-gray-700">Cantidad</th>
              <th class="px-3 py-2 text-right font-semibold text-gray-700">P. Unitario</th>
              <th class="px-3 py-2 text-right font-semibold text-gray-700">Descuento</th>
              <th class="px-3 py-2 text-right font-semibold text-gray-700">Total línea</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            @forelse ($venta->detalle as $d)
              <tr>
                <td class="px-3 py-2">{{ $d->producto?->nombre ?? ('Producto #'.$d->producto_id) }}</td>
                <td class="px-3 py-2 text-right">{{ number_format($d->cantidad, 2) }}</td>
                <td class="px-3 py-2 text-right">${{ number_format($d->precio_unitario, 2) }}</td>
                <td class="px-3 py-2 text-right">${{ number_format($d->descuento ?? 0, 2) }}</td>
                <td class="px-3 py-2 text-right font-medium">${{ number_format($d->total_linea, 2) }}</td>
              </tr>
            @empty
              <tr>
                <td colspan="5" class="px-3 py-6 text-center text-gray-500">
                  No hay partidas capturadas en esta venta.
                </td>
              </tr>
            @endforelse
          </tbody>
          <tfoot class="bg-gray-50">
            <tr>
              <td colspan="3"></td>
              <td class="px-3 py-2 text-right font-medium">Subtotal</td>
              <td class="px-3 py-2 text-right">${{ number_format($venta->subtotal, 2) }}</td>
            </tr>
            <tr>
              <td colspan="3"></td>
              <td class="px-3 py-2 text-right font-medium">IVA (16%)</td>
              <td class="px-3 py-2 text-right">${{ number_format($venta->iva, 2) }}</td>
            </tr>
            <tr>
              <td colspan="3"></td>
              <td class="px-3 py-2 text-right font-semibold">Total</td>
              <td class="px-3 py-2 text-right font-semibold">${{ number_format($venta->total, 2) }}</td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>

    {{-- Acciones inferiores --}}
    <div class="flex items-center justify-end gap-2 print:hidden">
      @if(in_array($venta->estatus, ['borrador','prefactura']))
        <form method="POST"
              action="{{ route('ventas.convertirPrefactura', $venta) }}"
              onsubmit="return confirm('¿Convertir a venta facturada y descontar stock?');">
          @csrf
          <button class="inline-flex items-center gap-2 h-10 px-4 rounded-xl bg-green-600 text-white text-sm font-medium hover:bg-green-700">
            <span class="material-symbols-outlined mi text-base">task_alt</span>
            Confirmar venta
          </button>
        </form>
      @endif

      <a href="{{ route('ventas.pdf', $venta) }}"
         target="_blank" rel="noopener"
         class="inline-flex items-center gap-2 h-10 px-4 rounded-xl border border-gray-300 text-gray-700 text-sm hover:bg-gray-50">
        <span class="material-symbols-outlined mi text-base">picture_as_pdf</span>
        PDF
      </a>

      <button type="button" onclick="window.print()"
              class="inline-flex items-center gap-2 h-10 px-4 rounded-xl border border-gray-300 text-gray-700 text-sm hover:bg-gray-50">
        <span class="material-symbols-outlined mi text-base">print</span>
        Imprimir
      </button>

      @if($venta->estatus !== 'cancelada')
        <a href="{{ route('ventas.edit', $venta) }}"
           class="inline-flex items-center gap-2 h-10 px-4 rounded-xl border border-gray-300 text-gray-700 text-sm hover:bg-gray-50">
          <span class="material-symbols-outlined mi text-base">edit</span>
          Editar
        </a>
      @endif

      <form method="POST" action="{{ route('ventas.destroy', $venta) }}"
            onsubmit="return confirm('¿Eliminar esta venta?');">
        @csrf
        @method('DELETE')
        <button class="inline-flex items-center gap-2 h-10 px-4 rounded-xl text-red-700 border border-red-300 hover:bg-red-50">
          <span class="material-symbols-outlined mi text-base">delete</span>
          Eliminar
        </button>
      </form>
    </div>
  </div>

  {{-- Estilos de impresión: oculta botones/headers --}}
  <style>
    @media print {
      header, .print\:hidden, .mi { display: none !important; }
      a[href]:after { content: ""; }
      body { color: #111; }
    }
  </style>
</x-app-layout>
