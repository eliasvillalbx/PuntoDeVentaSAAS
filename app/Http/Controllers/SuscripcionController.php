<?php
// app/Http/Controllers/SuscripcionController.php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSuscripcionRequest;
use App\Models\Suscripcion;
use App\Models\Empresa;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SuscripcionController extends Controller
{
    /** Alta tras pago correcto */
    public function store(StoreSuscripcionRequest $request): RedirectResponse
    {
        $data = $request->validated();

        try {
            return DB::transaction(function () use ($data) {
                $empresa = Empresa::findOrFail($data['empresa_id']);

                // Si quieres permitir una sola activa por empresa, valida:
                $hayActiva = Suscripcion::deEmpresa($empresa->id)->activa()
                    ->where('fecha_vencimiento', '>=', now())
                    ->exists();

                if ($hayActiva) {
                    return back()->withErrors('La empresa ya tiene una suscripción activa.');
                }

                $inicio = isset($data['fecha_inicio']) ? Carbon::parse($data['fecha_inicio']) : now();
                $venc   = Suscripcion::calcularVencimiento($inicio, $data['plan']);

                Suscripcion::create([
                    'empresa_id'       => $empresa->id,
                    'plan'             => $data['plan'],
                    'fecha_inicio'     => $inicio,
                    'fecha_vencimiento'=> $venc,
                    'estado'           => 'activa',
                    'renovado'         => false,
                ]);

                return back()->with('success', 'Suscripción activada.');
            });
        } catch (\Throwable $e) {
            Log::error('Error al crear suscripción', ['e' => $e]);
            return back()->withErrors('No se pudo activar la suscripción.');
        }
    }

    /** Renovación (pago exitoso después de vencer) */
    public function renew(Request $request, Suscripcion $suscripcion): RedirectResponse
    {
        try {
            // Renovamos solo si ya venció:
            if ($suscripcion->estado !== 'vencida') {
                return back()->withErrors('Solo puedes renovar suscripciones vencidas.');
            }

            $inicio = now(); // o el día posterior al vencimiento anterior
            $venc   = Suscripcion::calcularVencimiento($inicio, $suscripcion->plan);

            $suscripcion->update([
                'fecha_inicio'      => $inicio,
                'fecha_vencimiento' => $venc,
                'estado'            => 'activa',
                'renovado'          => true,
            ]);

            return back()->with('success', 'Suscripción renovada.');
        } catch (\Throwable $e) {
            Log::error('Error al renovar suscripción', ['e' => $e]);
            return back()->withErrors('No se pudo renovar.');
        }
    }
}
