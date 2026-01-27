<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\Suscripcion;
use App\Models\User;
use App\Notifications\SuscripcionPagadaNotification;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class BillingController extends Controller
{
    public function alert(Request $request)
    {
        $user = Auth::user();

        $empresaId = (int) data_get($user, 'id_empresa');
        $empresa   = $empresaId > 0 ? Empresa::find($empresaId) : null;

        $suscripcion = null;
        if ($empresa) {
            $suscripcion = Suscripcion::deEmpresa($empresa->id)
                ->orderByDesc('fecha_vencimiento')
                ->first();
        }

        $plans = config('clip.plans', []);

        return view('billing.alert', [
            'empresa'     => $empresa,
            'suscripcion' => $suscripcion,
            'plans'       => $plans,
        ]);
    }

    // ==========================================================
    // CLIP (PRESERVADO TAL CUAL)
    // ==========================================================
    public function createCheckout(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $empresaId = (int) data_get($user, 'id_empresa');
        if ($empresaId <= 0) {
            return back()->withErrors('Tu usuario no está vinculado a una empresa.');
        }

        $empresa = Empresa::find($empresaId);
        if (!$empresa) {
            return back()->withErrors('No se encontró la empresa vinculada a tu usuario.');
        }

        $data = $request->validate([
            'plan' => 'required|string|in:mensual,trimestral,anual',
        ]);

        $plan   = $data['plan'];
        $planes = config('clip.plans', []);

        if (!isset($planes[$plan])) {
            return back()->withErrors('El plan seleccionado no es válido.');
        }

        $planCfg     = $planes[$plan];
        $amount      = (float) $planCfg['amount'];
        $currency    = $planCfg['currency'] ?? 'MXN';
        $description = $planCfg['description'] ?? ($planCfg['label'] ?? $plan);

        $apiKey      = config('clip.api_key');
        $secret      = config('clip.secret');
        $checkoutUrl = config('clip.checkout_url', 'https://api.payclip.com/v2/checkout');

        if (empty($apiKey) || empty($secret)) {
            Log::error('Clip: faltan CLIP_API_KEY o CLIP_API_SECRET en .env', [
                'api_key' => $apiKey ? 'PRESENTE' : 'VACIO',
                'secret'  => $secret ? 'PRESENTE' : 'VACIO',
            ]);

            return back()->withErrors('La pasarela de pago no está configurada. Contacta al administrador.');
        }

        $rawCreds  = trim($apiKey) . ':' . trim($secret);
        $authToken = 'Basic ' . base64_encode($rawCreds);

        $meReferenceId = sprintf(
            'SUB-%d-%s-%s',
            $empresa->id,
            strtoupper($plan),
            now()->format('YmdHis')
        );
        if (strlen($meReferenceId) > 36) {
            $meReferenceId = substr($meReferenceId, 0, 36);
        }

        $successUrl = route('dashboard');
        $errorUrl   = route('billing.alert');
        $defaultUrl = route('billing.alert');

        $customerName  = $user->name ?? ($empresa->razon_social ?? 'Cliente');
        $customerEmail = $user->email ?? 'no-email@example.com';
        $defaultPhone  = config('clip.defaults.default_phone', '5599999999');

        $userPhoneRaw  = data_get($user, 'phone') ?? data_get($user, 'telefono');
        $customerPhone = $userPhoneRaw ? preg_replace('/\D+/', '', (string) $userPhoneRaw) : $defaultPhone;

        $billingAddress = config('clip.defaults.billing_address', []);

        $payload = [
            'amount'               => $amount,
            'currency'             => $currency,
            'purchase_description' => $description,

            'redirection_url'      => [
                'success' => $successUrl,
                'error'   => $errorUrl,
                'default' => $defaultUrl,
            ],

            'metadata'             => [
                'me_reference_id' => $meReferenceId,
                'customer_info'   => [
                    'name'  => $customerName,
                    'email' => $customerEmail,
                    'phone' => $customerPhone,
                ],
                'empresa_id'      => $empresa->id,
                'empresa_nombre'  => $empresa->razon_social,
                'plan'            => $plan,
                'user_id'         => $user->id,
            ],

            'webhook_url'          => route('clip.webhook', [], true),
            'billing_address'      => $billingAddress,
        ];

        try {
            $response = Http::withHeaders([
                    'Authorization' => $authToken,
                    'Content-Type'  => 'application/json',
                    'Accept'        => 'application/json',
                ])
                ->post($checkoutUrl, $payload);

            Log::info('Clip Checkout: respuesta al crear link de pago', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            if (!$response->successful()) {
                $msg = 'Error al crear link de pago Clip: ' . $response->body();
                return app()->environment('local')
                    ? back()->withInput()->withErrors($msg)
                    : back()->withErrors('No se pudo iniciar el pago con Clip. Inténtalo más tarde.');
            }

            $data = $response->json();
            $paymentUrl = $data['payment_request_url'] ?? null;

            if (!$paymentUrl) {
                Log::error('Clip Checkout: respuesta sin payment_request_url', ['data' => $data]);

                $msg = app()->environment('local')
                    ? 'Respuesta Clip sin payment_request_url: ' . json_encode($data)
                    : 'No se pudo obtener el link de pago. Inténtalo más tarde.';

                return back()->withInput()->withErrors($msg);
            }

            session()->flash('clip_me_reference_id', $meReferenceId);
            session()->flash('clip_payment_request_id', $data['payment_request_id'] ?? null);

            return redirect()->away($paymentUrl);

        } catch (\Throwable $e) {
            Log::error('Clip Checkout: excepción al llamar al endpoint', [
                'msg'  => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            $msg = app()->environment('local')
                ? 'Excepción al llamar a Clip: ' . $e->getMessage()
                : 'No se pudo iniciar el pago con Clip.';

            return back()->withInput()->withErrors($msg);
        }
    }

    // ==========================================================
    // STRIPE
    // ==========================================================
    public function createStripeCheckout(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $empresaId = (int) data_get($user, 'id_empresa');
        if ($empresaId <= 0) return back()->withErrors('Tu usuario no está vinculado a una empresa.');

        $empresa = Empresa::find($empresaId);
        if (!$empresa) return back()->withErrors('No se encontró la empresa vinculada a tu usuario.');

        $data = $request->validate([
            'plan' => 'required|string|in:mensual,trimestral,anual',
        ]);

        $uiPlan = $data['plan']; // mensual|trimestral|anual (UI)
        $planes = config('clip.plans', []);

        if (!isset($planes[$uiPlan])) {
            return back()->withErrors('El plan seleccionado no es válido.');
        }

        $planCfg     = $planes[$uiPlan];
        $amount      = (float) ($planCfg['amount'] ?? 0);
        $months      = (int) ($planCfg['months'] ?? 1);
        $currency    = strtoupper((string) ($planCfg['currency'] ?? config('stripe.currency', 'MXN')));
        $description = (string) ($planCfg['description'] ?? ($planCfg['label'] ?? $uiPlan));

        if ($amount <= 0) return back()->withErrors('El monto del plan no es válido. Revisa tu configuración.');

        $stripeSecret = config('stripe.secret');
        if (empty($stripeSecret)) {
            Log::error('Stripe: falta STRIPE_SECRET (o config/stripe.php no existe)');
            return back()->withErrors('Stripe no está configurado. Revisa STRIPE_SECRET.');
        }

        $unitAmount = (int) round($amount * 100);

        // mínimo Stripe para MXN: $10.00 => 1000 centavos
        $minByCurrency = ['MXN' => 1000, 'USD' => 50, 'EUR' => 50];
        $minUnitAmount = $minByCurrency[$currency] ?? null;
        if ($minUnitAmount !== null && $unitAmount < $minUnitAmount) {
            $minDisplay = number_format($minUnitAmount / 100, 2);
            return back()->withInput()->withErrors("El monto mínimo para {$currency} en Stripe es {$minDisplay} {$currency}. Ajusta el plan.");
        }

        // ✅ Map al ENUM real de tu BD (NO cambiamos BD)
        $dbPlan = $this->mapUiPlanToDbEnum($uiPlan);

        if ($dbPlan === null) {
            return back()->withInput()->withErrors('Este plan no está disponible con la configuración actual de la base de datos.');
        }

        $meReferenceId = sprintf('SUB-%d-%s-%s', $empresa->id, strtoupper($uiPlan), now()->format('YmdHis'));

        $successUrl = route('billing.stripe.success', [], true) . '?session_id={CHECKOUT_SESSION_ID}';
        $cancelUrl  = route('billing.stripe.cancel', [], true);

        try {
            \Stripe\Stripe::setApiKey($stripeSecret);

            $session = \Stripe\Checkout\Session::create([
                'mode' => 'payment',
                'payment_method_types' => ['card'],

                'line_items' => [[
                    'quantity' => 1,
                    'price_data' => [
                        'currency' => strtolower($currency),
                        'unit_amount' => $unitAmount,
                        'product_data' => ['name' => $description],
                    ],
                ]],

                'success_url' => $successUrl,
                'cancel_url'  => $cancelUrl,

                'client_reference_id' => $meReferenceId,

                'metadata' => [
                    'me_reference_id' => (string) $meReferenceId,
                    'empresa_id'      => (string) $empresa->id,
                    'plan'            => (string) $dbPlan,
                    'months'          => (string) max(1, $months),
                    'user_id'         => (string) $user->id,
                ],

                'customer_email' => $user->email ?? null,
            ]);

            if (empty($session->url)) {
                Log::error('Stripe Checkout: sesión creada sin URL', ['session_id' => $session->id ?? null]);
                return back()->withErrors('No se pudo iniciar el pago con Stripe (sin URL).');
            }

            session()->flash('stripe_me_reference_id', $meReferenceId);
            session()->flash('stripe_session_id', $session->id);

            return redirect()->away($session->url);

        } catch (\Throwable $e) {
            Log::error('Stripe Checkout: excepción al crear sesión', [
                'msg'  => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            $msg = app()->environment('local') ? ('Stripe error: ' . $e->getMessage()) : 'No se pudo iniciar el pago con Stripe.';
            return back()->withInput()->withErrors($msg);
        }
    }

    public function stripeSuccess(Request $request): RedirectResponse
    {
        $sessionId = (string) $request->query('session_id');
        if (!$sessionId) {
            return redirect()->route('billing.alert')->withErrors('Regresaste del pago sin session_id.');
        }

        $stripeSecret = config('stripe.secret');
        if (empty($stripeSecret)) {
            Log::error('Stripe Success: falta STRIPE_SECRET');
            return redirect()->route('billing.alert')->withErrors('Stripe no está configurado.');
        }

        try {
            \Stripe\Stripe::setApiKey($stripeSecret);

            $session = \Stripe\Checkout\Session::retrieve($sessionId);

            if (($session->payment_status ?? null) !== 'paid') {
                return redirect()->route('billing.alert')->withErrors('El pago aún no está confirmado. Espera unos minutos.');
            }

            // ✅ FIX metadata (y ahora también NOTIFICA)
            $this->bestEffortCreateOrRenewFromStripeSession($session);

            return redirect()->route('dashboard')
                ->with('status', 'Pago recibido. Si tu plan no se refleja de inmediato, se activará en unos momentos.');

        } catch (\Throwable $e) {
            Log::error('Stripe Success: excepción al recuperar sesión', [
                'msg' => $e->getMessage(),
                'session_id' => $sessionId,
            ]);

            $msg = app()->environment('local') ? ('Stripe retrieve error: ' . $e->getMessage()) : 'No se pudo confirmar el pago.';
            return redirect()->route('billing.alert')->withErrors($msg);
        }
    }

    public function stripeCancel(): RedirectResponse
    {
        return redirect()->route('billing.alert')->withErrors('Pago cancelado. Puedes intentarlo nuevamente.');
    }

    // ==========================================================
    // Helpers
    // ==========================================================
    private function bestEffortCreateOrRenewFromStripeSession(\Stripe\Checkout\Session $session): void
    {
        $meta = $session->metadata ? $session->metadata->toArray() : [];

        $empresaId = (int) ($meta['empresa_id'] ?? 0);
        $dbPlan    = trim((string) ($meta['plan'] ?? ''));
        $months    = (int) ($meta['months'] ?? 0);
        $userId    = (int) ($meta['user_id'] ?? 0);

        if ($empresaId <= 0 || $dbPlan === '') {
            Log::warning('Stripe best-effort: metadata incompleta', [
                'session_id' => $session->id ?? null,
                'metadata'   => $meta,
            ]);
            return;
        }

        // Para notificación
        $tipo = null; // 'creada' | 'renovada'
        $suscripcionId = null;

        DB::transaction(function () use ($empresaId, $dbPlan, $months, $session, &$tipo, &$suscripcionId) {

            $yaActiva = Suscripcion::deEmpresa($empresaId)
                ->where('estado', 'activa')
                ->where('fecha_vencimiento', '>=', now())
                ->exists();

            if ($yaActiva) {
                Log::info('Stripe best-effort: ya existe activa vigente, se ignora', [
                    'empresa_id' => $empresaId,
                    'session_id' => $session->id ?? null,
                ]);
                return;
            }

            $inicio = now()->startOfDay();
            $venc   = $this->calcVencimientoFromDbPlan($inicio, $dbPlan, $months);

            $ultima = Suscripcion::deEmpresa($empresaId)
                ->orderByDesc('fecha_vencimiento')
                ->lockForUpdate()
                ->first();

            if ($ultima) {
                $estaVencida = ($ultima->estado === 'vencida') || Carbon::parse($ultima->fecha_vencimiento)->isPast();

                if ($estaVencida) {
                    $ultima->update([
                        'plan'              => $dbPlan,
                        'fecha_inicio'      => $inicio->toDateTimeString(),
                        'fecha_vencimiento' => $venc->toDateTimeString(),
                        'estado'            => 'activa',
                        'renovado'          => true,
                    ]);

                    $tipo = 'renovada';
                    $suscripcionId = $ultima->id;

                    Log::info('Stripe best-effort: suscripción RENOVADA', [
                        'empresa_id' => $empresaId,
                        'suscripcion_id' => $ultima->id,
                        'plan' => $dbPlan,
                        'venc' => $venc->toDateTimeString(),
                        'session_id' => $session->id ?? null,
                    ]);
                    return;
                }
            }

            $nueva = Suscripcion::create([
                'empresa_id'        => $empresaId,
                'plan'              => $dbPlan,
                'fecha_inicio'      => $inicio->toDateTimeString(),
                'fecha_vencimiento' => $venc->toDateTimeString(),
                'estado'            => 'activa',
                'renovado'          => false,
            ]);

            $tipo = 'creada';
            $suscripcionId = $nueva->id;

            Log::info('Stripe best-effort: suscripción CREADA', [
                'empresa_id' => $empresaId,
                'plan' => $dbPlan,
                'venc' => $venc->toDateTimeString(),
                'session_id' => $session->id ?? null,
            ]);
        });

        // ✅ Notificar SOLO si realmente se creó/renovó (si ya había activa, no)
        if ($tipo && $suscripcionId) {
            $this->notifyAEsPagoStripe(
                empresaId: $empresaId,
                suscripcionId: $suscripcionId,
                planDb: $dbPlan,
                sessionId: (string) ($session->id ?? ''),
                tipo: $tipo
            );
        } else {
            Log::info('Stripe Notify: no se notificó (sin cambios)', [
                'empresa_id' => $empresaId,
                'session_id' => $session->id ?? null,
            ]);
        }
    }

    /**
     * ✅ NOTIFICACIÓN A AEs (administrador_empresa) por sistema (DB) + correo.
     * No te cambia Users, roles, ni nada extra.
     */
    private function notifyAEsPagoStripe(int $empresaId, int $suscripcionId, string $planDb, string $sessionId, string $tipo): void
    {
        try {
            $empresa = Empresa::find($empresaId);

            // AEs de esa empresa
            $aes = User::query()
                ->where('id_empresa', $empresaId)
                ->role('administrador_empresa')
                ->get();

            Log::info('Stripe Notify: AEs encontrados', [
                'empresa_id' => $empresaId,
                'count' => $aes->count(),
                'ids' => $aes->pluck('id')->all(),
                'session_id' => $sessionId,
                'tipo' => $tipo,
            ]);

            if ($aes->isEmpty()) {
                Log::warning('Stripe Notify: no hay AEs con rol administrador_empresa en la empresa', [
                    'empresa_id' => $empresaId,
                    'session_id' => $sessionId,
                ]);
                return;
            }

            Notification::send($aes, new SuscripcionPagadaNotification(
                empresaId: $empresaId,
                empresaNombre: $empresa->razon_social ?? ('Empresa #' . $empresaId),
                suscripcionId: $suscripcionId,
                plan: $planDb,
                stripeSessionId: $sessionId,
                tipo: $tipo
            ));

            Log::info('Stripe Notify: notificación enviada', [
                'empresa_id' => $empresaId,
                'to' => $aes->pluck('email')->all(),
                'session_id' => $sessionId,
            ]);

        } catch (\Throwable $e) {
            Log::error('Stripe Notify: excepción', [
                'empresa_id' => $empresaId,
                'msg' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
        }
    }

    private function mapUiPlanToDbEnum(string $uiPlan): ?string
    {
        $uiPlan = strtolower(trim($uiPlan));

        return match ($uiPlan) {
            'mensual'    => '1_mes',
            'trimestral' => '6_meses',
            'anual'      => '1_año',
            default      => null,
        };
    }

    private function calcVencimientoFromDbPlan(Carbon $inicio, string $dbPlan, int $months): Carbon
    {
        $dbPlan = trim($dbPlan);

        $m = match ($dbPlan) {
            '1_mes'   => 1,
            '6_meses' => 6,
            '1_año'   => 12,
            '3_años'  => 36,
            default   => ($months > 0 ? $months : 1),
        };

        return (clone $inicio)->addMonthsNoOverflow(max(1, $m));
    }
}
