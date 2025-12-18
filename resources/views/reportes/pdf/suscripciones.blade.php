<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <style>
    @page { margin: 28px; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111827; }
    .grid { display: table; width: 100%; margin-bottom: 20px; }
    .col { display: table-cell; vertical-align: top; }
    .w-33 { width: 33.33%; text-align: center; padding: 10px; background: #f8fafc; border: 1px solid #e2e8f0; }
    .text-right { text-align: right; }
    .muted { color: #64748b; font-size: 10px; text-transform: uppercase; letter-spacing: 0.5px; }
    .title { font-size: 18px; font-weight: bold; color: #334155; margin-bottom: 5px; }
    .val { font-size: 18px; font-weight: bold; color: #0f172a; display: block; margin-top: 5px; }
    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    th, td { border: 1px solid #e2e8f0; padding: 8px; }
    th { background: #f1f5f9; color: #475569; font-size: 10px; text-transform: uppercase; }
    .badge { padding: 3px 8px; border-radius: 99px; font-size: 9px; color: #fff; font-weight: bold; }
    .bg-green { background: #10b981; }
    .bg-red { background: #ef4444; }
    .bg-gray { background: #94a3b8; }
  </style>
</head>
<body>
  <div style="text-align:center; margin-bottom:30px;">
    <div class="title">Reporte Global de Suscripciones</div>
    <div class="muted" style="text-transform: none;">Generado el: {{ date('d/m/Y H:i') }}</div>
  </div>
  <div class="grid">
    <div class="col w-33">
        <span class="muted">Empresas Activas</span>
        <span class="val" style="color:#10b981;">{{ $activas }}</span>
    </div>
    <div class="col w-33" style="border-left:0; border-right:0;">
        <span class="muted">Empresas Inactivas</span>
        <span class="val" style="color:#64748b;">{{ $inactivas }}</span>
    </div>
    <div class="col w-33">
        <span class="muted">MRR Estimado (Mensual)</span>
        <span class="val" style="color:#3b82f6;">${{ number_format($mrr, 2) }}</span>
    </div>
  </div>
  <table>
    <thead>
      <tr>
        <th>Empresa</th>
        <th>Plan Actual</th>
        <th>Estado</th>
        <th>Vencimiento</th>
        <th class="text-right">Precio Plan</th>
      </tr>
    </thead>
    <tbody>
      @forelse($suscripciones as $sub)
        <tr>
          <td>
            <div style="font-weight:bold; color:#334155;">{{ $sub->empresa->razon_social }}</div>
            <div style="font-size:10px; color:#64748b;">{{ $sub->empresa->email }}</div>
          </td>
          <td>{{ ucfirst(str_replace('_', ' ', $sub->plan ?? 'Estándar')) }}</td>
          <td>
             @if($sub->estado == 'activa') <span class="badge bg-green">ACTIVA</span>
             @elseif($sub->estado == 'vencida') <span class="badge bg-red">VENCIDA</span>
             @else <span class="badge bg-gray">{{ strtoupper($sub->estado) }}</span> @endif
          </td>
          <td>{{ \Carbon\Carbon::parse($sub->fecha_vencimiento)->format('d/m/Y') }}</td>
          <td class="text-right">${{ number_format($sub->precio_calculado, 2) }}</td>
        </tr>
      @empty
        <tr><td colspan="5" class="text-center" style="padding:20px; color:#94a3b8;">No se encontraron suscripciones.</td></tr>
      @endforelse
    </tbody>
  </table>
</body>
</html>