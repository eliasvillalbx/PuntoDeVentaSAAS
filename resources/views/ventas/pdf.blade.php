{{-- resources/views/ventas/pdf.blade.php --}}
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <style>
    @page { margin: 28px 28px 40px 28px; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111827; }
    h1, h2, h3 { margin: 0 0 8px 0; }
    .mb-1 { margin-bottom: 4px; }
    .mb-2 { margin-bottom: 8px; }
    .mb-3 { margin-bottom: 12px; }
    .mb-4 { margin-bottom: 16px; }
    .grid { display: table; width: 100%; }
    .col { display: table-cell; vertical-align: top; }
    .w-50 { width: 50%; }
    .text-right { text-align: right; }
    .text-center { text-align: center; }
    .muted { color: #6b7280; }
    .badge { display: inline-block; padding: 2px 6px; border-radius: 999px; font-size: 11px; border: 1px solid #e5e7eb; }
    .badge-gray { background: #f3f4f6; color: #374151; }
    .badge-amber { background: #FEF3C7; color: #92400E; }
    .badge-green { background: #D1FAE5; color: #065F46; }
    .badge-red { background: #FEE2E2; color: #991B1B; }
    table { width: 100%; border-collapse: collapse; }
    th, td { border: 1px solid #e5e7eb; padding: 6px; }
    th { background: #f3f4f6; }
    .totals td { border: none; }
    .hr { height: 1px; background: #e5e7eb; border: 0; margin: 10px 0; }
    .logo { max-height: 60px; }
  </style>
</head>
<body>

  {{-- Encabezado con logo y datos de empresa --}}
  <div class="grid mb-3">
    <div class="col w-50">
      @if (!empty($logoDataUri))
        <img class="logo" src="{{ $logoDataUri }}" alt="Logo">
      @endif
      <div style="font-size:16px; font-weight:bold; margin-top:6px;">
        {{ $venta->empresa->nombre_comercial ?? $venta->empresa->razon_social ?? ('Empresa #'.$venta->empresa_id) }}
      </div>
      @if(!empty($venta->empresa->rfc))
        <div class="muted">RFC: {{ $venta->empresa->rfc }}</div>
      @endif
      @if(!empty($venta->empresa->sitio_web))
        <div class="muted">Web: {{ $venta->empresa->sitio_web }}</div>
      @endif
    </div>
    <div class="col w-50 text-right">
      <h2>Venta #{{ $venta->id }}</h2>
      <div class="muted">Fecha: {{ $venta->fecha_venta?->format('d/m/Y') }}</div>
      @php
        $badgeClass = [
          'borrador'   => 'badge badge-gray',
          'prefactura' => 'badge badge-amber',
          'facturada'  => 'badge badge-green',
          'cancelada'  => 'badge badge-red',
        ][$venta->estatus] ?? 'badge badge-gray';
      @endphp
      <div class="mb-2"><span class="{{ $badgeClass }}">{{ ucfirst($venta->estatus) }}</span></div>
    </div>
  </div>

  <hr class="hr"/>

  {{-- Datos de operación --}}
  <div class="grid mb-3">
    <div class="col w-50">
      <div class="mb-1"><strong>Vendedor:</strong>
        {{ $venta->usuario?->nombre_completo ?? ($venta->usuario?->nombre ?? '') }}
      </div>
      <div class="mb-1"><strong>Cliente:</strong>
        {{ $venta->cliente?->nombre ?? $venta->cliente?->razon_social ?? 'Venta directa' }}
      </div>
    </div>
    <div class="col w-50">
      @if(!empty($venta->observaciones))
        <div class="mb-1"><strong>Observaciones:</strong></div>
        <div class="muted">{{ $venta->observaciones }}</div>
      @endif
    </div>
  </div>

  {{-- Detalle --}}
  <table class="mb-3">
    <thead>
      <tr>
        <th>Producto</th>
        <th class="text-right">Cantidad</th>
        <th class="text-right">Precio</th>
        <th class="text-right">Desc</th>
        <th class="text-right">Total</th>
      </tr>
    </thead>
    <tbody>
      @foreach($venta->detalle as $d)
        <tr>
          <td>{{ $d->producto->nombre ?? ('#'.$d->producto_id) }}</td>
          <td class="text-right">{{ number_format($d->cantidad, 2) }}</td>
          <td class="text-right">${{ number_format($d->precio_unitario, 2) }}</td>
          <td class="text-right">${{ number_format($d->descuento ?? 0, 2) }}</td>
          <td class="text-right">${{ number_format($d->total_linea, 2) }}</td>
        </tr>
      @endforeach
    </tbody>
  </table>

  {{-- Totales --}}
  <table class="totals" style="width: 40%; margin-left:auto;">
    <tr>
      <td class="text-right">Subtotal</td>
      <td class="text-right" style="width: 120px;">${{ number_format($venta->subtotal, 2) }}</td>
    </tr>
    <tr>
      <td class="text-right">IVA</td>
      <td class="text-right">${{ number_format($venta->iva, 2) }}</td>
    </tr>
    <tr>
      <td class="text-right"><strong>Total</strong></td>
      <td class="text-right"><strong>${{ number_format($venta->total, 2) }}</strong></td>
    </tr>
  </table>

</body>
</html>
