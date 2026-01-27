<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Configuration\Exceptions;

// === Middlewares que usaremos como alias ===
use App\Http\Middleware\CheckSuscripcionActiva;

// (Opcional, si usas Spatie Permission)
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        // api: __DIR__ . '/../routes/api.php', // <- descomenta si usas API
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        /**
         * Alias de middlewares (equivalente a $middlewareAliases del Kernel).
         * Esto garantiza que Route::middleware('suscripcion.activa') funcione.
         */
        $middleware->alias([
            // Tu alias de suscripción
            'suscripcion.activa' => CheckSuscripcionActiva::class,

            // Spatie (roles/permisos)
            'role'               => RoleMiddleware::class,
            'permission'         => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);

        /**
         * ✅ EXCEPCIONES CSRF PARA WEBHOOKS (NO ELIMINA NADA)
         * - Clip y Stripe envían POST desde fuera, así que CSRF debe excluirse en estas rutas.
         * - Aun así, tú sigues protegiendo el webhook con firmas/verificación (Stripe) y lógica de seguridad.
         */
        $middleware->validateCsrfTokens(except: [
            'clip/webhook',
            'webhooks/clip-checkout',
            'stripe/webhook',
        ]);

        /**
         * Aquí también podrías:
         * - Añadir middlewares globales con $middleware->append(...)
         * - Sobrescribir grupos 'web'/'api' con $middleware->web([...]) / $middleware->api([...])
         * En la mayoría de los casos no es necesario tocarlo.
         */
    })
    ->withExceptions(function (Exceptions $exceptions) {
        /**
         * Punto central para configurar manejo de excepciones/reportables en L12.
         * Lo dejamos default. Si quieres logs específicos:
         *
         * $exceptions->report(function (Throwable $e) {
         *     // custom report
         * });
         *
         * $exceptions->render(function (Throwable $e, $request) {
         *     // custom render
         * });
         */
    })
    ->create();
