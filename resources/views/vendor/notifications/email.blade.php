{{-- resources/views/vendor/notifications/email.blade.php --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="color-scheme" content="light">
  <meta name="supported-color-schemes" content="light">
  <title>{{ $subject ?? config('app.name') }}</title>
</head>
<body style="margin:0; padding:0; background-color:#ffffff; font-family:ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, 'Apple Color Emoji','Segoe UI Emoji'; color:#0f172a;">
  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#ffffff;">
    <tr>
      <td align="center" style="padding:24px 16px;">
        {{-- Container --}}
        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:640px;">
          {{-- Header --}}
          <tr>
            <td style="padding:8px 0 18px 0;">
              <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                <tr>
                  <td align="left" style="padding:0;">
                    {{-- Logo (estilo similar al login) --}}
                    <div style="display:inline-block; line-height:1;">
                      <div style="font-weight:900; font-size:28px; letter-spacing:-0.04em; color:#0f172a;">
                        POS
                      </div>
                      <div style="margin-top:2px; font-weight:700; font-size:11px; letter-spacing:0.25em; text-transform:uppercase; color:#64748b;">
                        Empresarial
                      </div>
                    </div>
                  </td>
                  <td align="right" style="padding:0; font-size:12px; color:#94a3b8;">
                    {{ config('app.name') }}
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          {{-- Divider --}}
          <tr>
            <td style="height:1px; background:linear-gradient(90deg, rgba(0,0,0,.06), rgba(0,0,0,.02), rgba(0,0,0,.06)); line-height:1px; font-size:0;">
              &nbsp;
            </td>
          </tr>

          {{-- Card --}}
          <tr>
            <td style="padding:18px 0 0 0;">
              <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0"
                     style="border:1px solid #e2e8f0; border-radius:16px; overflow:hidden;">
                <tr>
                  <td style="padding:22px 20px; background-color:#ffffff;">
                    {{-- Title / Greeting --}}
                    @php
                      $computedGreeting = $greeting ?? null;

                      if (! $computedGreeting) {
                        if (! empty($level) && $level === 'error') {
                          $computedGreeting = 'Ups…';
                        } else {
                          $computedGreeting = 'Hola 👋';
                        }
                      }
                    @endphp

                    <div style="font-size:22px; font-weight:800; color:#0f172a; margin:0 0 8px 0;">
                      {{ $computedGreeting }}
                    </div>

                    @if (!empty($introLines))
                      @foreach ($introLines as $line)
                        <p style="margin:0 0 12px 0; font-size:14px; line-height:1.6; color:#334155;">
                          {{ $line }}
                        </p>
                      @endforeach
                    @endif

                    {{-- Action Button --}}
                    @isset($actionText)
                      @php
                        $buttonBg = '#0f172a';      // slate-900
                        $buttonHover = '#1e293b';   // slate-800 (no hover real en email, solo referencia)
                        $buttonText = '#ffffff';

                        if (! empty($level) && $level === 'error') {
                          $buttonBg = '#b91c1c';    // red-700
                        } elseif (! empty($level) && $level === 'success') {
                          $buttonBg = '#047857';    // emerald-700
                        }
                      @endphp

                      <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin:18px 0 18px 0;">
                        <tr>
                          <td align="left">
                            <a href="{{ $actionUrl }}"
                               style="display:inline-block; background:{{ $buttonBg }}; color:{{ $buttonText }}; text-decoration:none; font-size:14px; font-weight:700; padding:12px 16px; border-radius:10px;">
                              {{ $actionText }}
                            </a>
                          </td>
                        </tr>
                      </table>
                    @endisset

                    {{-- Outro lines --}}
                    @if (!empty($outroLines))
                      @foreach ($outroLines as $line)
                        <p style="margin:0 0 12px 0; font-size:14px; line-height:1.6; color:#334155;">
                          {{ $line }}
                        </p>
                      @endforeach
                    @endif

                    {{-- Salutation --}}
                    @if (!empty($salutation))
                      <p style="margin:18px 0 0 0; font-size:14px; color:#334155;">
                        {{ $salutation }}
                      </p>
                    @else
                      <p style="margin:18px 0 0 0; font-size:14px; color:#334155;">
                        Saludos,<br>
                        <strong style="color:#0f172a;">{{ config('app.name') }}</strong>
                      </p>
                    @endif

                    {{-- Subcopy (fallback link) --}}
                    @isset($actionText)
                      <div style="margin-top:18px; padding-top:14px; border-top:1px solid #e2e8f0;">
                        <p style="margin:0; font-size:12px; line-height:1.6; color:#64748b;">
                          Si el botón no funciona, copia y pega este enlace en tu navegador:
                        </p>
                        <p style="margin:6px 0 0 0; font-size:12px; line-height:1.6; color:#64748b; word-break:break-all;">
                          <a href="{{ $actionUrl }}" style="color:#0f172a; text-decoration:underline;">
                            {{ $actionUrl }}
                          </a>
                        </p>
                      </div>
                    @endisset
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          {{-- Footer --}}
          <tr>
            <td style="padding:16px 0 0 0; text-align:center;">
              <p style="margin:0; font-size:12px; color:#94a3b8;">
                &copy; {{ date('Y') }} POS Empresarial. Todos los derechos reservados.
              </p>
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>
</body>
</html>
