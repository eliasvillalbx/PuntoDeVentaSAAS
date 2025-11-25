<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\Suscripcion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ClipWebhookController extends Controller
{
    /**
     * Maneja notificaciones del Checkout Webhook de Clip.
     *
     * Estructura ejemplo del webhook (Checkout): ver docs
     * - resource: "CHECKOUT"
     * - resource_status: CREATED | PENDING | COMPLETED | CANCELED | EXPIRED
     * - payment_request_id: UUID
     * - me_reference_id: tu referencia (si la enviaste al crear el link)
     */
    public function handle(Request $request)
    {
        $payload = $request->all();

        Log::info('Clip Webhook recibido', [
            'payload' => $payload,
        ]);

        $resource          = $payload['resource']         ?? null;
        $resourceStatus    = $payload['resource_status']  ?? null;
        $paymentRequestId  = $payload['payment_request_id'] ?? null;
        $meReferenceId     = $payload['me_reference_id']  ?? null;

        // Solo nos interesan eventos de CHECKOUT con payment_request_id
        if ($resource !== 'CHECKOUT' || !$paymentRequestId) {
            Log::warning('Clip Webhook ignorado: resource no CHECKOUT o sin payment_request_id', [
                'resource'         => $resource,
                'payment_request_id' => $paymentRequestId,
            ]);

            // Siempre responde 200 para que Clip no siga reintentando
            return response()->json(['ok' => true]);
        }

        // Logueamos otros estados, pero solo actuamos cuando es COMPLETED
        if ($resourceStatus !== 'COMPLETED') {
            Log::info('Clip Webhook: estado no COMPLETED, solo se registra', [
                'payment_request_id' => $paymentRequestId,
                'status'             => $resourceStatus,
                'me_reference_id'    => $meReferenceId,
            ]);

            return response()->json(['ok' => true]);
        }

        // Si llegamos aquí: CHECKOUT + COMPLETED → hay un pago exitoso
        try {
            $this->processCompletedCheckout($paymentRequestId, $meReferenceId);
        } catch (\Throwable $e) {
            Log::error('Clip Webhook: error procesando pago COMPLETED', [
                'msg'   => $e->getMessage(),
                'file'  => $e->getFile(),
                'line'  => $e->getLine(),
                'payment_request_id' => $paymentRequestId,
            ]);
            // Aun así respondemos 200 para evitar reintentos infinitos
        }

        return response()->json(['ok' => true]);
    }

    /**
     * Lógica para procesar un CHECKOUT COMPLETED.
     * - Consulta GET /v2/checkout/{payment_request_id} para obtener metadata.
     * - Usa metadata.empresa_id y metadata.plan.
     * - Crea o renueva la suscripción de esa empresa.
     */
    protected function processCompletedCheckout(string $paymentRequestId, ?string $meReferenceId = null): void
    {
        $apiKey = config('clip.api_key');
        $secret = config('clip.secret');

        if (empty($apiKey) || empty($secret)) {
            Log::error('Clip Webhook: faltan credenciales CLIP_API_KEY o CLIP_API_SECRET');
            return;
        }

        // Token Basic para autenticar contra GET /v2/checkout/{payment_request_id}
        $authToken = 'Basic ' . base64_encode(trim($apiKey) . ':' . trim($secret));

        // Endpoint base que ya usas para POST; aquí le agregamos /{payment_request_id}
        $baseUrl  = config('clip.checkout_url', 'https://api.payclip.com/v2/checkout');
        $endpoint = rtrim($baseUrl, '/') . '/' . $paymentRequestId;

        try {
            $response = Http::withHeaders([
                    'Authorization' => $authToken,
                    'Accept'        => 'application/json',
                ])
                ->get($endpoint);

            if (!$response->successful()) {
                Log::error('Clip Webhook: error al consultar estado del link de pago', [
                    'endpoint' => $endpoint,
                    'status'   => $response->status(),
                    'body'     => $response->body(),
                ]);
                return;
            }

            $checkout = $response->json();

            // El objeto de checkout v2 incluye metadata con lo que tú enviaste
            // cuando creaste el link de pago. Ahí pusimos empresa_id y plan.
            $metadata   = $checkout['metadata'] ?? [];
            $empresaId  = (int) ($metadata['empresa_id'] ?? 0);
            $plan       = $metadata['plan']      ?? null;
            $status     = $checkout['status']    ?? null; // p.ej. CHECKOUT_COMPLETED

            Log::info('Clip Webhook: checkout v2 consultado', [
                'payment_request_id' => $paymentRequestId,
                'status'             => $status,
                'empresa_id'         => $empresaId,
                'plan'               => $plan,
                'me_reference_id'    => $meReferenceId,
            ]);

            if ($empresaId <= 0 || !$plan) {
                Log::warning('Clip Webhook: metadata sin empresa_id o plan, no se puede crear/renovar suscripción', [
                    'metadata' => $metadata,
                ]);
                return;
            }

            // Crear o renovar suscripción
            $this->createOrRenewSubscription($empresaId, $plan);

        } catch (\Throwable $e) {
            Log::error('Clip Webhook: excepción consultando checkout v2', [
                'msg'   => $e->getMessage(),
                'file'  => $e->getFile(),
                'line'  => $e->getLine(),
                'payment_request_id' => $paymentRequestId,
            ]);
        }
    }

    /**
     * Crea una suscripción nueva si no hay ninguna,
     * o renueva la última suscripción de la empresa si ya existe.
     */
    protected function createOrRenewSubscription(int $empresaId, string $plan): void
    {
        DB::transaction(function () use ($empresaId, $plan) {
            $empresa = Empresa::find($empresaId);

            if (!$empresa) {
                Log::warning('Clip Webhook: empresa no encontrada al intentar crear/renovar suscripción', [
                    'empresa_id' => $empresaId,
                ]);
                return;
            }

            // Buscamos la última suscripción de la empresa (activa o vencida)
            $ultima = Suscripcion::deEmpresa($empresaId)
                ->orderByDesc('fecha_vencimiento')
                ->first();

            $inicio = now()->startOfDay();
            $venc   = Suscripcion::calcularVencimiento($inicio, $plan);

            if (!$ultima) {
                // No hay ninguna suscripción previa → creamos una nueva
                Suscripcion::create([
                    'empresa_id'        => $empresa->id,
                    'plan'              => $plan,                        // "mensual", "trimestral", "anual"
                    'fecha_inicio'      => $inicio->toDateTimeString(),
                    'fecha_vencimiento' => $venc->toDateTimeString(),
                    'estado'            => 'activa',
                    'renovado'          => false,
                ]);

                Log::info('Clip Webhook: suscripción creada por pago COMPLETED', [
                    'empresa_id' => $empresa->id,
                    'plan'       => $plan,
                ]);
            } else {
                // Ya existía una suscripción → la "renovamos": nuevo periodo
                $ultima->update([
                    'plan'              => $plan,
                    'fecha_inicio'      => $inicio->toDateTimeString(),
                    'fecha_vencimiento' => $venc->toDateTimeString(),
                    'estado'            => 'activa',
                    'renovado'          => true,
                ]);

                Log::info('Clip Webhook: suscripción renovada por pago COMPLETED', [
                    'suscripcion_id' => $ultima->id,
                    'empresa_id'     => $empresa->id,
                    'plan'           => $plan,
                ]);
            }
        });
    }
}
