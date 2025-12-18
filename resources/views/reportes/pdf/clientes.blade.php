<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <style>
    @page { margin: 28px; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111827; }
    .grid { display: table; width: 100%; margin-bottom: 20px; border-bottom: 1px solid #e5e7eb; padding-bottom: 10px; }
    .col { display: table-cell; vertical-align: middle; }
    .w-20 { width: 20%; } .w-50 { width: 50%; } .w-30 { width: 30%; }
    .text-right { text-align: right; }
    .muted { color: #6b7280; font-size: 10px; }
    .logo { max-height: 60px; max-width: 150px; }
    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    th, td { border: 1px solid #e5e7eb; padding: 6px; }
    th { background: #eff6ff; font-size: 10px; text-transform: uppercase; color: #1e3a8a; }
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
        <div class="muted">Reporte de Clientes</div>
    </div>
    <div class="col w-30 text-right">
        <h2 style="font-size: 16px; margin:0;">Mejores Clientes</h2>
        <div class="muted">{{ date('d/m/Y') }}</div>
    </div>
  </div>
  <table>
    <thead>
      <tr><th>Cliente</th><th class="text-right">Compras</th><th class="text-right">Ticket Prom.</th><th class="text-right">Total Gastado</th></tr>
    </thead>
    <tbody>
      @forelse($clientes as $cte)
        <tr>
          <td><div style="font-weight:bold;">{{ $cte->razon_social ?: ($cte->nombre . ' ' . $cte->apellido_paterno) }}</div></td>
          <td class="text-right">{{ $cte->frecuencia_compra }}</td>
          <td class="text-right">${{ number_format($cte->ticket_promedio, 2) }}</td>
          <td class="text-right" style="background-color: #f9fafb;"><strong>${{ number_format($cte->total_gastado, 2) }}</strong></td>
        </tr>
      @empty
        <tr><td colspan="4" class="text-center" style="padding:15px;">Sin datos de clientes.</td></tr>
      @endforelse
    </tbody>
  </table>
</body>
</html>