<!doctype html>
<html lang="es">
  <body style="margin:0; padding:0; font-family:Arial, Helvetica, sans-serif; background:#f8fafc;">
    <div style="max-width:640px; margin:0 auto; padding:24px;">
      <div style="background:#ffffff; border:1px solid #e2e8f0; border-radius:16px; overflow:hidden;">
        <div style="padding:20px 20px 10px 20px;">
          <div style="font-weight:900; font-size:24px; color:#0f172a; letter-spacing:-0.04em;">POS</div>
          <div style="font-weight:700; font-size:11px; color:#64748b; letter-spacing:0.25em; text-transform:uppercase;">Empresarial</div>
        </div>

        <div style="height:1px; background:#e2e8f0;"></div>

        <div style="padding:18px 20px 20px 20px; color:#0f172a;">
          <h2 style="margin:0 0 8px 0; font-size:18px;">Hola {{ $nombre }} 👋</h2>
          <p style="margin:0 0 14px 0; font-size:14px; line-height:1.6; color:#334155;">
            Se creó tu cuenta. Aquí están tus credenciales de acceso:
          </p>

          <div style="background:#f1f5f9; border:1px solid #e2e8f0; padding:12px; border-radius:12px; font-size:14px;">
            <div><strong>Correo:</strong> {{ $email }}</div>
            <div style="margin-top:6px;"><strong>Contraseña temporal:</strong> {{ $password }}</div>
          </div>

          <p style="margin:14px 0 0 0; font-size:13px; line-height:1.6; color:#475569;">
            Por seguridad, te recomendamos cambiar la contraseña al iniciar sesión.
          </p>
        </div>

        <div style="padding:14px 20px; background:#f8fafc; border-top:1px solid #e2e8f0; font-size:12px; color:#64748b; text-align:center;">
          &copy; {{ date('Y') }} POS Empresarial
        </div>
      </div>
    </div>
  </body>
</html>
