<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\Suscripcion;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BillingController extends Controller
{
    /**
     * Muestra la pantalla de aviso cuando la suscripción está inactiva o vencida.
     * Recupera datos de la empresa y los planes de pago disponibles.
     */
    public function alert(Request $request)
    {
        // 1. Obtiene el usuario actual
        $user = Auth::user();

        // 2. Busca la empresa asociada al usuario
        $empresaId = (int) data_get($user, 'id_empresa');
        $empresa   = $empresaId > 0 ? Empresa::find($empresaId) : null;

        // 3. Verifica si existe una suscripción previa
        $suscripcion = null;
        if ($empresa) {
            $suscripcion = Suscripcion::deEmpresa($empresa->id)
                ->orderByDesc('fecha_vencimiento') // Obtiene la más reciente
                ->first();
        }

        // 4. Carga los planes de precios desde la configuración
        $plans = config('clip.plans', []);

        // 5. Retorna la vista con la información necesaria
        return view('billing.alert', [
            'empresa'     => $empresa,
            'suscripcion' => $suscripcion,
            'plans'       => $plans,
        ]);
    }

    /**
     * Crea el link de pago en Clip (Checkout Redireccionado v2)
     * y redirige al usuario al payment_request_url.
     */
    public function createCheckout(Request $request): RedirectResponse
    {
        $user = Auth::user();

        // El usuario debe estar vinculado a una empresa
        $empresaId = (int) data_get($user, 'id_empresa');
        if ($empresaId <= 0) {
            return back()->withErrors('Tu usuario no está vinculado a una empresa.');
        }

        $empresa = Empresa::find($empresaId);
        if (!$empresa) {
            return back()->withErrors('No se encontró la empresa vinculada a tu usuario.');
        }

        // Plan seleccionado desde el formulario
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
        $description = $planCfg['description'] ?? $planCfg['label'];

        // Credenciales y endpoint de Clip
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

        // Token de autenticación "Basic base64(api_key:secret)"
        $rawCreds  = trim($apiKey) . ':' . trim($secret);
        $authToken = 'Basic ' . base64_encode($rawCreds);

        // me_reference_id (referencia propia para rastrear el pago)
        $meReferenceId = sprintf(
            'SUB-%d-%s-%s',
            $empresa->id,
            strtoupper($plan),
            now()->format('YmdHis')
        );
        if (strlen($meReferenceId) > 36) {
            $meReferenceId = substr($meReferenceId, 0, 36);
        }

        // URLs de regreso desde Clip (MUY IMPORTANTE)
        $successUrl = route('dashboard');       // pago exitoso
        $errorUrl   = route('billing.alert');   // error interno de checkout
        $defaultUrl = route('billing.alert');   // cancelación o cierre

        // Datos de cliente (metadata.customer_info requerido por SDK/React)
        $customerName  = $user->name ?? ($empresa->razon_social ?? 'Cliente');
        $customerEmail = $user->email ?? 'no-email@example.com';
        $defaultPhone  = config('clip.defaults.default_phone', '5599999999');

        // Intentamos sacar teléfono del usuario; si no, usamos uno default
        $userPhoneRaw = data_get($user, 'phone') ?? data_get($user, 'telefono');
        $customerPhone = $userPhoneRaw ? preg_replace('/\D+/', '', (string) $userPhoneRaw) : $defaultPhone;

        // Dirección de facturación: usamos los defaults
        $billingAddress = config('clip.defaults.billing_address', []);

        // Armamos el payload completo al estilo clip-sdk (para que el React no truene)
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
                // Datos extra tuyos (no requeridos, pero útiles)
                'empresa_id'      => $empresa->id,
                'empresa_nombre'  => $empresa->razon_social,
                'plan'            => $plan,
                'user_id'         => $user->id,
            ],

            'webhook_url'          => route('clip.webhook', [], true),

            'billing_address'      => $billingAddress,

            // Opcionales de v2 (puedes tunearlos después):
            // 'tip_enabled'       => false,
            // 'payment_method_types' => ['debit', 'credit', 'cash', 'bank_transfer'],
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

                if (app()->environment('local')) {
                    // En local mostramos el mensaje real para depurar
                    return back()->withInput()->withErrors($msg);
                }

                return back()->withErrors('No se pudo iniciar el pago con Clip. Inténtalo más tarde.');
            }

            $data = $response->json();

            $paymentUrl = $data['payment_request_url'] ?? null;

            if (!$paymentUrl) {
                Log::error('Clip Checkout: respuesta sin payment_request_url', [
                    'data' => $data,
                ]);

                $msg = app()->environment('local')
                    ? 'Respuesta Clip sin payment_request_url: ' . json_encode($data)
                    : 'No se pudo obtener el link de pago. Inténtalo más tarde.';

                return back()->withInput()->withErrors($msg);
            }

            // Guardamos referencias por si quieres mostrar algo al regresar
            session()->flash('clip_me_reference_id', $meReferenceId);
            session()->flash('clip_payment_request_id', $data['payment_request_id'] ?? null);

            // Redirigimos al checkout de Clip (React vive ahí)
            return redirect()->away($paymentUrl);

        } catch (\Throwable $e) {
            Log::error('Clip Checkout: excepción al llamar al endpoint', [
                'msg'  => $e->getMessage(),
                'file'=> $e->getFile(),
                'line'=> $e->getLine(),
            ]);

            $msg = app()->environment('local')
                ? 'Excepción al llamar a Clip: ' . $e->getMessage()
                : 'No se pudo iniciar el pago con Clip.';

            return back()->withInput()->withErrors($msg);
        }
    }
}
