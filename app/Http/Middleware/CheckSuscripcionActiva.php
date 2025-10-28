<?php
// app/Http/Middleware/CheckSuscripcionActiva.php

namespace App\Http\Middleware;

use App\Models\Suscripcion;
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

            // Evitar bucle si la alerta estuviera bajo este middleware por error
            if ($request->routeIs('billing.alert')) {
                return $next($request);
            }

            $user = Auth::user();

            // BYPASS para superadmin
            if (method_exists($user, 'hasRole') && $user->hasRole('superadmin')) {
                return $next($request);
            }

            // Debe estar vinculado a una empresa
            $empresaId = (int) data_get($user, 'id_empresa');
            if ($empresaId <= 0) {
                Log::warning('Usuario sin empresa intenta acceder con suscripcion.activa', ['uid' => $user->id]);
                return redirect()->route('billing.alert')
                    ->with('error', 'Tu cuenta no está vinculada a una empresa.');
            }

            // Validar suscripción vigente de la empresa:
            // - estado = activa
            // - fecha_inicio <= ahora
            // - fecha_vencimiento >= ahora
            $ahora = now();
            $activa = Suscripcion::query()
                ->where('empresa_id', $empresaId)
                ->where('estado', 'activa')
                ->where('fecha_inicio', '<=', $ahora)
                ->where('fecha_vencimiento', '>=', $ahora)
                ->exists();

            if (!$activa) {
                Log::info('Empresa sin suscripción vigente', ['uid' => $user->id, 'empresa_id' => $empresaId]);
                return redirect()->route('billing.alert')
                    ->with('error', 'Tu suscripción no está activa.');
            }

            return $next($request);
        } catch (\Throwable $e) {
            Log::error('CheckSuscripcionActiva falló', [
                'msg'  => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'uid'  => Auth::id(),
            ]);
            abort(500, 'No se pudo validar tu suscripción en este momento.');
        }
    }
}
