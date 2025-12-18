<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <style>
    @page { margin: 28px; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111827; }
    .grid { display: table; width: 100%; margin-bottom: 20px; border-bottom: 1px solid #e5e7eb; padding-bottom: 10px; }
    .col { display: table-cell; vertical-align: middle; }
    .w-20 { width: 20%; }
    .w-50 { width: 50%; }
    .w-30 { width: 30%; }
    .text-right { text-align: right; }
    .text-center { text-align: center; }
    .muted { color: #6b7280; font-size: 10px; }
    .logo { max-height: 60px; max-width: 150px; }
    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    th, td { border: 1px solid #e5e7eb; padding: 6px; }
    th { background: #f3f4f6; font-size: 10px; text-transform: uppercase; color: #374151; }
    .positive { color: #059669; font-weight: bold; }
    .negative { color: #dc2626; font-weight: bold; }
    .badge { font-size: 8px; padding: 2px 4px; border-radius: 3px; background: #e5e7eb; color: #374151; display: inline-block; margin-top: 2px;}
  </style>
</head>
<body>
  @php
    $logoBase64 = null;
    if(isset($empresa) && $empresa->logo_path) {
        $path = storage_path('app/public/' . $empresa->logo_path);
        if(!file_exists($path)) $path = public_path('storage/' . $empresa->logo_path);
        if(file_exists($path)) {
            $type = pathinfo($path, PATHINFO_EXTENSION);
            $data = file_get_contents($path);
            $logoBase64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
        }
    }
  @endphp
  <div class="grid">
    <div class="col w-20">@if($logoBase64) <img src="{{ $logoBase64 }}" class="logo"> @endif</div>
    <div class="col w-50">
        <div style="font-size:14px; font-weight:bold;">{{ $empresa->razon_social ?? 'Empresa' }}</div>
        <div class="muted">Reporte de Rentabilidad Real</div>
    </div>
    <div class="col w-30 text-right">
        <h2 style="font-size: 16px; margin:0;">Rentabilidad</h2>
        <div class="muted">{{ date('d/m/Y H:i') }}</div>
    </div>
  </div>
  <table>
    <thead>
      <tr>
        <th>Producto</th>
        <th class="text-right">Costo Real</th>
        <th class="text-right">Vendidos</th>
        <th class="text-right">Ingresos</th>
        <th class="text-right">Ganancia</th>
        <th class="text-right">Margen</th>
      </tr>
    </thead>
    <tbody>
      @forelse($productos as $prod)
        <tr>
          <td>
            <div style="font-weight:bold;">{{ $prod->nombre }}</div>
            <div class="muted">{{ $prod->sku ?? 'S/N' }}</div>
          </td>
          <td class="text-right">
            ${{ number_format($prod->costo_calculado, 2) }}<br>
            @if($prod->origen_costo == 'Promedio Compras') <span class="badge" style="background:#d1fae5; color:#065f46;">HISTORIAL</span>
            @elseif($prod->origen_costo == 'Media Proveedores') <span class="badge" style="background:#dbeafe; color:#1e40af;">PROV. AVG</span>
            @else <span class="badge">REF</span> @endif
          </td>
          <td class="text-right">{{ number_format($prod->unidades_vendidas, 0) }}</td>
          <td class="text-right">${{ number_format($prod->ingresos_totales, 2) }}</td>
          <td class="text-right {{ $prod->ganancia_neta >= 0 ? 'positive' : 'negative' }}">${{ number_format($prod->ganancia_neta, 2) }}</td>
          <td class="text-right">{{ number_format($prod->margen, 1) }}%</td>
        </tr>
      @empty
        <tr><td colspan="6" class="text-center" style="padding: 20px; color: #9ca3af;">No hay datos disponibles.</td></tr>
      @endforelse
    </tbody>
  </table>
  <div style="margin-top: 20px; font-size: 9px; color: #666; border-top:1px solid #eee; padding-top:5px;">
    <strong>Leyenda:</strong> <span class="badge" style="background:#d1fae5; color:#065f46;">HISTORIAL</span> Costo real de compras. <span class="badge" style="background:#dbeafe; color:#1e40af;">PROV. AVG</span> Promedio de proveedores. <span class="badge">REF</span> Costo referencial.
  </div>
</body>
</html>