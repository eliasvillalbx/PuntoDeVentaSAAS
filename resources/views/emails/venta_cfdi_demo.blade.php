<!doctype html>
<html lang="es">
  <body style="margin:0; padding:0; font-family:Arial, Helvetica, sans-serif; background:#f8fafc;">
    <div style="max-width:640px; margin:0 auto; padding:24px;">
      <div style="background:#ffffff; border:1px solid #e2e8f0; border-radius:16px; overflow:hidden;">

        <!-- Header (igual que el ejemplo POS Empresarial) -->
        <div style="padding:20px 20px 10px 20px;">
          <div style="font-weight:900; font-size:24px; color:#0f172a; letter-spacing:-0.04em;">
            POS
          </div>
          <div style="font-weight:700; font-size:11px; color:#64748b; letter-spacing:0.25em; text-transform:uppercase;">
            Empresarial
          </div>
        </div>

        <div style="height:1px; background:#e2e8f0;"></div>

        <!-- Body -->
        <div style="padding:18px 20px 20px 20px; color:#0f172a;">
          <h2 style="margin:0 0 8px 0; font-size:18px;">
            Factura DEMO - Venta #{{ $venta->id }}
          </h2>

          <p style="margin:0 0 14px 0; font-size:14px; line-height:1.6; color:#334155;">
            Adjuntamos <strong>XML</strong> y <strong>PDF</strong> de la venta
            <strong>#{{ $venta->id }}</strong>.
          </p>

          <!-- Receptor box -->
          <div style="background:#f1f5f9; border:1px solid #e2e8f0; padding:12px; border-radius:12px; font-size:14px;">
            <div><strong>RFC receptor:</strong> {{ $rfcCliente }}</div>
            <div style="margin-top:6px;"><strong>Correo receptor:</strong> {{ $emailCliente }}</div>
          </div>

          <!-- Warning / demo -->
          <div style="margin-top:14px; background:#fffbeb; border:1px solid #f59e0b; padding:12px; border-radius:12px; font-size:13px; line-height:1.6; color:#92400e;">
            <strong>Nota:</strong> Este documento es <strong>DEMO (SIN TIMBRAR)</strong>.
            No es CFDI válido ante SAT hasta timbrarse con un PAC.
          </div>

          <p style="margin:14px 0 0 0; font-size:13px; line-height:1.6; color:#475569;">
            Si tienes dudas sobre la venta o tus archivos adjuntos, responde a este correo.
          </p>
        </div>

        <!-- Footer -->
        <div style="padding:14px 20px; background:#f8fafc; border-top:1px solid #e2e8f0; font-size:12px; color:#64748b; text-align:center;">
          &copy; {{ date('Y') }} POS Empresarial
        </div>

      </div>
    </div>
  </body>
</html>
