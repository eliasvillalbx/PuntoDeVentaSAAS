<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CheckSuscripcionActiva
{
    public function handle(Request $request, Closure $next)
    {
        try {
            if (!Auth::check()) {
                return redirect()->route('login');
            }

            $user = Auth::user();

            // === BYPASS SUPERADMIN ===
            if (method_exists($user, 'hasRole') && $user->hasRole('superadmin')) {
                return $next($request);
            }

            // === LÓGICA DE SUSCRIPCIÓN === (ajusta a tu modelo)
            $activa = (bool) data_get($user, 'suscripcion_activa', false);

            if (!$activa) {
                // IMPORTANTE: NO redirijas a /dashboard aquí
                return redirect()->route('billing.alert')
                    ->with('error', 'Tu suscripción no está activa.');
            }

            return $next($request);
        } catch (\Throwable $e) {
            Log::error('CheckSuscripcionActiva fallo', [
                'msg' => $e->getMessage(),
                'file'=> $e->getFile(),
                'line'=> $e->getLine(),
                'uid' => Auth::id(),
            ]);
            abort(500, 'No se pudo validar tu suscripción en este momento.');
        }
    }
}
