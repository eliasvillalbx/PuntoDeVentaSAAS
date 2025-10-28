<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * Si manejas rate limits personalizados, puedes declararlos aquí.
     * public const HOME = '/dashboard'; // (opcional)
     */

    public function boot(): void
    {
        /**
         * ===== Registrar alias de middleware por router (Plan B) =====
         * Esto registra 'suscripcion.activa' incluso si el Kernel estuviera mal cacheado.
         * Si ya lo tienes en Kernel ($middlewareAliases), puedes dejar ambas cosas sin problema.
         */
        $this->app['router']->aliasMiddleware(
            'suscripcion.activa',
            \App\Http\Middleware\CheckSuscripcionActiva::class
        );

        /**
         * ===== Definir archivos de rutas =====
         */
        $this->routes(function () {
            Route::middleware('web')
                ->group(base_path('routes/web.php'));

            Route::prefix('api')
                ->middleware('api')
                ->group(base_path('routes/api.php'));
        });
    }
}
