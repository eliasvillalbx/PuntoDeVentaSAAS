<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <style>
    @page { margin: 28px; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111827; }
    
    /* Layout */
    .grid { display: table; width: 100%; margin-bottom: 20px; border-bottom: 1px solid #e5e7eb; padding-bottom: 10px; }
    .col { display: table-cell; vertical-align: middle; }
    .w-20 { width: 20%; } 
    .w-50 { width: 50%; } 
    .w-30 { width: 30%; }
    
    /* Text Helpers */
    .text-right { text-align: right; }
    .muted { color: #6b7280; font-size: 10px; }
    .logo { max-height: 60px; max-width: 150px; }
    
    /* Table */
    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    th, td { border: 1px solid #e5e7eb; padding: 6px; }
    th { background: #fffbeb; font-size: 10px; text-transform: uppercase; color: #92400e; font-weight: bold; }
    
    /* Badges & Highlights */
    .badge-red { background: #fee2e2; color: #991b1b; padding: 2px 6px; border-radius: 4px; font-weight: bold; font-size: 10px; }
    
    .badge-cost { 
        font-size: 8px; 
        padding: 2px 4px; 
        border-radius: 3px; 
        background: #f3f4f6; 
        color: #374151; 
        display: inline-block; 
        margin-top: 3px; 
        text-transform: uppercase;
        font-weight: bold;
    }

    .total-box { font-size: 16px; font-weight: bold; color: #d97706; }
  </style>
</head>
<body>

  {{-- LÓGICA DE LOGO BASE64 --}}
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

  {{-- ENCABEZADO --}}
  <div class="grid">
    <div class="col w-20">
        @if($logoBase64) <img src="{{ $logoBase64 }}" class="logo"> @endif
    </div>
    <div class="col w-50">
        <div style="font-size:14px; font-weight:bold;">{{ $empresa->razon_social ?? 'Empresa' }}</div>
        <div class="muted">Reporte de Inventario Valorizado</div>
        <div class="muted">Generado: {{ date('d/m/Y H:i') }}</div>
    </div>
    <div class="col w-30 text-right">
        <h2 style="font-size: 16px; margin:0; color: #374151;">Inventario Total</h2>
        <div class="total-box">${{ number_format($valorTotal, 2) }}</div>
    </div>
  </div>

  {{-- TABLA --}}
  <table>
    <thead>
      <tr>
        <th>Producto</th>
        <th>Categoría</th>
        <th class="text-right">Stock</th>
        <th class="text-right">Costo Unitario</th>
        <th class="text-right">Valor Total</th>
      </tr>
    </thead>
    <tbody>
      @forelse($inventario as $item)
        <tr>
          <td>
            <div style="font-weight:bold; color: #111827;">{{ $item->nombre }}</div>
            <div class="muted">{{ $item->sku ?? 'S/N' }}</div>
          </td>
          <td>
            {{ $item->categoria ? $item->categoria->nombre : 'General' }}
          </td>
          <td class="text-right">
            {{-- Alerta de Stock Bajo --}}
            @if(isset($stockBajo) && $item->stock <= $stockBajo) 
                <span class="badge-red">{{ $item->stock }}</span>
            @elseif($item->stock <= 5)
                <span class="badge-red">{{ $item->stock }}</span>
            @else 
                {{ $item->stock }} 
            @endif
          </td>
          <td class="text-right">
            ${{ number_format($item->costo_calculado, 2) }}
            <br>
            {{-- Etiqueta de Origen del Costo --}}
            @if($item->origen_costo == 'Promedio Compras')
                <span class="badge-cost" style="background:#d1fae5; color:#065f46;">HISTORIAL</span>
            @elseif($item->origen_costo == 'Media Proveedores')
                <span class="badge-cost" style="background:#dbeafe; color:#1e40af;">PROV. AVG</span>
            @else
                <span class="badge-cost">REF</span>
            @endif
          </td>
          <td class="text-right font-bold">
            ${{ number_format($item->valor_stock_real, 2) }}
          </td>
        </tr>
      @empty
        <tr>
            <td colspan="5" style="text-align:center; padding:20px; color:#9ca3af;">
                No se encontraron productos en el inventario con los filtros seleccionados.
            </td>
        </tr>
      @endforelse
    </tbody>
  </table>

  {{-- LEYENDA --}}
  <div style="margin-top: 20px; font-size: 9px; color: #666; border-top:1px solid #eee; padding-top:5px;">
    <strong>Nota de Valorización:</strong> El valor del inventario se calcula usando el costo más preciso disponible (Cascada):<br>
    <span class="badge-cost" style="background:#d1fae5; color:#065f46;">HISTORIAL</span> Promedio ponderado de compras reales recibidas.<br>
    <span class="badge-cost" style="background:#dbeafe; color:#1e40af;">PROV. AVG</span> Promedio de costos registrados con proveedores (sin historial de compra).<br>
    <span class="badge-cost">REF</span> Costo referencial manual (sin historial ni proveedores).
  </div>

</body>
</html>