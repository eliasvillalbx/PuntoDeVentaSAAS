<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\Suscripcion;
use App\Models\User;
use App\Notifications\SuscripcionPagadaNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class StripeWebhookController extends Controller
{
    /**
     * POST /stripe/webhook
     * Recuerda excluir CSRF para esta ruta.
     */
    public function handle(Request $request)
    {
        $secret = config('stripe.webhook_secret') ?: env('STRIPE_WEBHOOK_SECRET');

        if (empty($secret)) {
            Log::error('Stripe Webhook: falta STRIPE_WEBHOOK_SECRET');
            return response()->json(['error' => 'Webhook not configured'], 500);
        }

        $payload   = $request->getContent();
        $sigHeader = (string) $request->header('Stripe-Signature');

        try {
            $event = \Stripe\Webhook::constructEvent($payload, $sigHeader, $secret);
        } catch (\UnexpectedValueException $e) {
            Log::warning('Stripe Webhook: payload inválido', ['msg' => $e->getMessage()]);
            return response()->json(['error' => 'Invalid payload'], 400);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            Log::warning('Stripe Webhook: firma inválida', ['msg' => $e->getMessage()]);
            return response()->json(['error' => 'Invalid signature'], 400);
        } catch (\Throwable $e) {
            Log::error('Stripe Webhook: error verificando evento', [
                'msg'  => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return response()->json(['error' => 'Webhook error'], 500);
        }

        $eventId = (string) ($event->id ?? '');
        if ($eventId !== '') {
            $k = "stripe_event_processed:{$eventId}";
            if (!Cache::add($k, true, now()->addDays(7))) {
                return response()->json(['status' => 'duplicate_ignored'], 200);
            }
        }

        try {
            $type = (string) ($event->type ?? '');

            switch ($type) {
                case 'checkout.session.completed':
                    /** @var \Stripe\Checkout\Session $session */
                    $session = $event->data->object;

                    // Si quieres estrictamente pagado:
                    // if (($session->payment_status ?? null) !== 'paid') break;

                    $this->onCheckoutSessionCompleted($session);
                    break;

                default:
                    break;
            }

            return response()->json(['status' => 'ok'], 200);

        } catch (\Throwable $e) {
            Log::error('Stripe Webhook: fallo procesando evento', [
                'type' => $event->type ?? null,
                'msg'  => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json(['error' => 'Processing error'], 500);
        }
    }

    /**
     * Crea/renueva suscripción según metadata.plan (ENUM BD) y notifica AEs.
     */
    private function onCheckoutSessionCompleted(\Stripe\Checkout\Session $session): void
    {
        $meta = $session->metadata ? $session->metadata->toArray() : [];

        $empresaId = (int) ($meta['empresa_id'] ?? 0);
        $dbPlan    = trim((string) ($meta['plan'] ?? '')); // ENUM BD
        $months    = (int) ($meta['months'] ?? 0);

        if ($empresaId <= 0 || $dbPlan === '') {
            Log::warning('Stripe Webhook: metadata incompleta', [
                'session_id' => $session->id ?? null,
                'metadata'   => $meta,
            ]);
            return;
        }

        $allowedPlans = ['1_mes', '6_meses', '1_año', '3_años'];
        if (!in_array($dbPlan, $allowedPlans, true)) {
            Log::warning('Stripe Webhook: plan no permitido', [
                'empresa_id' => $empresaId,
                'plan'       => $dbPlan,
                'session_id' => $session->id ?? null,
            ]);
            return;
        }

        if ($months <= 0) {
            $months = $this->monthsFromDbPlan($dbPlan);
        }

        $inicio = now()->startOfDay();
        $venc   = (clone $inicio)->addMonthsNoOverflow(max(1, $months));

        $empresaNombre = 'Empresa #' . $empresaId;
        try {
            $empresa = Empresa::find($empresaId);
            if ($empresa) $empresaNombre = $empresa->razon_social ?? $empresaNombre;
        } catch (\Throwable $e) {}

        $renovada = false;
        $suscripcionId = null;

        DB::transaction(function () use ($empresaId, $dbPlan, $inicio, $venc, $session, &$renovada, &$suscripcionId) {

            $yaActiva = Suscripcion::deEmpresa($empresaId)
                ->where('estado', 'activa')
                ->where('fecha_vencimiento', '>=', now())
                ->exists();

            if ($yaActiva) {
                Log::info('Stripe Webhook: ya existe suscripción activa vigente, se ignora', [
                    'empresa_id' => $empresaId,
                    'session_id' => $session->id ?? null,
                ]);
                return;
            }

            $ultima = Suscripcion::deEmpresa($empresaId)
                ->orderByDesc('fecha_vencimiento')
                ->lockForUpdate()
                ->first();

            if ($ultima) {
                $estaVencida = false;
                try {
                    $estaVencida = ($ultima->estado === 'vencida') || Carbon::parse($ultima->fecha_vencimiento)->isPast();
                } catch (\Throwable $e) {
                    $estaVencida = ($ultima->estado === 'vencida');
                }

                if ($estaVencida) {
                    $ultima->update([
                        'plan'              => $dbPlan,
                        'fecha_inicio'      => $inicio->toDateTimeString(),
                        'fecha_vencimiento' => $venc->toDateTimeString(),
                        'estado'            => 'activa',
                        'renovado'          => true,
                    ]);

                    $renovada = true;
                    $suscripcionId = $ultima->id;

                    Log::info('Stripe Webhook: suscripción RENOVADA', [
                        'empresa_id'      => $empresaId,
                        'suscripcion_id'  => $ultima->id,
                        'plan'            => $dbPlan,
                        'venc'            => $venc->toDateTimeString(),
                        'session_id'      => $session->id ?? null,
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

            $renovada = false;
            $suscripcionId = $nueva->id;

            Log::info('Stripe Webhook: suscripción CREADA', [
                'empresa_id' => $empresaId,
                'plan'       => $dbPlan,
                'venc'       => $venc->toDateTimeString(),
                'session_id' => $session->id ?? null,
            ]);
        });

        if (!$suscripcionId) {
            return;
        }

        $this->notifyAEs(
            empresaId: $empresaId,
            empresaNombre: $empresaNombre,
            planDb: $dbPlan,
            fechaInicio: $inicio->toDateTimeString(),
            fechaVenc: $venc->toDateTimeString(),
            sessionId: (string) ($session->id ?? ''),
            renovada: $renovada
        );
    }

    private function notifyAEs(
        int $empresaId,
        string $empresaNombre,
        string $planDb,
        string $fechaInicio,
        string $fechaVenc,
        ?string $sessionId,
        bool $renovada
    ): void {
        try {
            if (!empty($sessionId)) {
                $key = "stripe_webhook_notify_ae:{$sessionId}";
                if (!Cache::add($key, true, now()->addDays(3))) {
                    return;
                }
            }

            $aes = User::query()
                ->where('id_empresa', $empresaId)
                ->role('administrador_empresa')
                ->get();

            Log::info('Stripe Webhook Notify: buscando AEs', [
                'empresa_id' => $empresaId,
                'count' => $aes->count(),
                'ids' => $aes->pluck('id')->all(),
                'session_id' => $sessionId,
            ]);

            if ($aes->isEmpty()) {
                Log::warning('Stripe Webhook Notify: NO hay AEs para notificar', [
                    'empresa_id' => $empresaId,
                    'session_id' => $sessionId,
                ]);
                return;
            }

            Notification::send($aes, new SuscripcionPagadaNotification(
                provider: 'stripe',
                empresaId: $empresaId,
                empresaNombre: $empresaNombre,
                planDb: $planDb,
                planLabel: $this->planLabelFromDb($planDb),
                fechaInicio: $fechaInicio,
                fechaVenc: $fechaVenc,
                amountCents: null,
                currency: null,
                reference: $sessionId ?: null,
                renovada: $renovada
            ));

            Log::info('Stripe Webhook Notify: notificación enviada a AEs', [
                'empresa_id' => $empresaId,
                'count' => $aes->count(),
                'session_id' => $sessionId,
            ]);

        } catch (\Throwable $e) {
            Log::error('Stripe Webhook Notify: excepción notificando', [
                'empresa_id' => $empresaId,
                'msg'  => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
        }
    }

    private function planLabelFromDb(string $dbPlan): string
    {
        return match ($dbPlan) {
            '1_mes'   => 'Mensual',
            '6_meses' => 'Semestral',
            '1_año'   => 'Anual',
            '3_años'  => '3 Años',
            default   => $dbPlan,
        };
    }

    private function monthsFromDbPlan(string $dbPlan): int
    {
        return match ($dbPlan) {
            '1_mes'   => 1,
            '6_meses' => 6,
            '1_año'   => 12,
            '3_años'  => 36,
            default   => 1,
        };
    }
}
