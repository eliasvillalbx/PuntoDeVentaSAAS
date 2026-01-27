<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSuscripcionRequest;
use App\Http\Requests\UpdateSuscripcionRequest;
use App\Models\Empresa;
use App\Models\Suscripcion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SuscripcionController extends Controller
{
    public function __construct()
    {
        // Recomendado según tu estrategia: permisos en constructor (opcional).
        //$this->middleware(['auth','verified']);
        // $this->middleware('can:suscripciones.ver')->only(['index','show']);
        // $this->middleware('can:suscripciones.crear')->only(['create','store']);
        // $this->middleware('can:suscripciones.editar')->only(['edit','update','renew']);
        // $this->middleware('can:suscripciones.eliminar')->only(['destroy']);
    }


    public static function calcularVencimiento(Carbon $inicio, string $plan): Carbon
{
    $plan = strtolower($plan);

    $months = match ($plan) {
        // UI
        'mensual'     => 1,
        'trimestral'  => 3,
        'anual'       => 12,

        // BD enum
        '1_mes'       => 1,
        '6_meses'     => 6,
        '1_año'       => 12,
        '3_años'      => 36,

        default       => 1,
    };

    return (clone $inicio)->addMonthsNoOverflow($months);
}

    public function index(Request $request)
    {
        $qEmpresa = (int) $request->integer('empresa_id');
        $qPlan    = $request->get('plan');
        $qEstado  = $request->get('estado');
        $qTexto   = trim((string) $request->get('q',''));

        $empresas = Empresa::orderBy('razon_social')->get();

        $suscripciones = Suscripcion::query()
            ->with('empresa:id,razon_social')
            ->when($qEmpresa > 0, fn($q) => $q->where('empresa_id', $qEmpresa))
            ->when(in_array($qPlan, ['1_mes','6_meses','1_año','3_años'], true), fn($q) => $q->where('plan', $qPlan))
            ->when(in_array($qEstado, ['activa','vencida'], true), fn($q) => $q->where('estado', $qEstado))
            ->when($qTexto !== '', fn($q) => $q->whereHas('empresa', fn($w) => $w->where('razon_social','like',"%{$qTexto}%")))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('suscripciones.index', compact('suscripciones','empresas','qEmpresa','qPlan','qEstado','qTexto'));
    }

    public function create()
    {
        $empresas = Empresa::orderBy('razon_social')->get();
        return view('suscripciones.create', compact('empresas'));
    }

    /** Alta tras pago correcto */
    public function store(StoreSuscripcionRequest $request): RedirectResponse
    {
        $data = $request->validated();

        try {
            return DB::transaction(function () use ($data) {
                $empresa = Empresa::findOrFail($data['empresa_id']);

                // Una sola vigente por empresa
                $hayActiva = Suscripcion::deEmpresa($empresa->id)->activa()->exists();
                if ($hayActiva) {
                    return back()->withInput()->withErrors('La empresa ya tiene una suscripción activa.');
                }

                $inicio = isset($data['fecha_inicio'])
                    ? Carbon::parse($data['fecha_inicio'])->startOfDay()
                    : now()->startOfDay();

                $venc = Suscripcion::calcularVencimiento($inicio, $data['plan']);

                Suscripcion::create([
                    'empresa_id'        => $empresa->id,
                    'plan'              => $data['plan'],                         // ENUM válido
                    'fecha_inicio'      => $inicio->toDateTimeString(),          // DATETIME
                    'fecha_vencimiento' => $venc->toDateTimeString(),            // DATETIME
                    'estado'            => 'activa',
                    'renovado'          => false,
                ]);

                return redirect()->route('suscripciones.index')->with('success','Suscripción activada.');
            });
        } catch (\Throwable $e) {
            Log::error('Error al crear suscripción', ['e' => $e]);
            $msg = app()->environment('local')
                ? ($e->getMessage() . ' (revisa que el plan coincida con el ENUM y que las columnas existan)')
                : 'No se pudo activar la suscripción.';
            return back()->withInput()->withErrors($msg);
        }
    }

    public function show(Suscripcion $suscripcion)
    {
        $suscripcion->load('empresa');
        return view('suscripciones.show', compact('suscripcion'));
    }

    public function edit(Suscripcion $suscripcion)
    {
        $suscripcion->load('empresa');
        $empresas = Empresa::orderBy('razon_social')->get();
        return view('suscripciones.edit', compact('suscripcion','empresas'));
    }

    public function update(UpdateSuscripcionRequest $request, Suscripcion $suscripcion): RedirectResponse
    {
        $data = $request->validated();

        try {
            return DB::transaction(function () use ($data, $suscripcion) {

                // Si quedará "activa", validar que no exista otra vigente en la empresa
                if ($data['estado'] === 'activa') {
                    $existeOtraActiva = Suscripcion::deEmpresa((int)$data['empresa_id'])
                        ->activa()
                        ->where('id', '<>', $suscripcion->id)
                        ->exists();
                    if ($existeOtraActiva) {
                        return back()->withInput()->withErrors('Ya existe otra suscripción activa para la empresa.');
                    }
                }

                $inicio = Carbon::parse($data['fecha_inicio'])->startOfDay();
                $venc   = Suscripcion::calcularVencimiento($inicio, $data['plan']);

                $suscripcion->update([
                    'empresa_id'        => (int) $data['empresa_id'],
                    'plan'              => $data['plan'],                         // ENUM válido
                    'fecha_inicio'      => $inicio->toDateTimeString(),
                    'fecha_vencimiento' => $venc->toDateTimeString(),
                    'estado'            => $data['estado'],                       // activa | vencida
                ]);

                return redirect()->route('suscripciones.show', $suscripcion)->with('success','Suscripción actualizada.');
            });
        } catch (\Throwable $e) {
            Log::error('Error al actualizar suscripción', ['e' => $e]);
            $msg = app()->environment('local')
                ? ($e->getMessage() . ' (verifica ENUM plan/estado y columnas datetime)')
                : 'No se pudo actualizar la suscripción.';
            return back()->withInput()->withErrors($msg);
        }
    }

    public function destroy(Suscripcion $suscripcion): RedirectResponse
    {
        try {
            $suscripcion->delete();
            return redirect()->route('suscripciones.index')->with('success','Suscripción eliminada.');
        } catch (\Throwable $e) {
            Log::error('Error al eliminar suscripción', ['e' => $e]);
            return back()->withErrors('No se pudo eliminar la suscripción.');
        }
    }

    /**
     * Reactiva una suscripción vencida tras un pago exitoso.
     * Calcula las nuevas fechas y cambia el estado a 'activa'.
     */
    public function renew(Request $request, Suscripcion $suscripcion): RedirectResponse
    {
        try {
            // 1. Validación de seguridad
            // Impide renovar si la suscripción aún está vigente para evitar errores
            if ($suscripcion->estado !== 'vencida' && !$suscripcion->fecha_vencimiento->isPast()) {
                return back()->withErrors('Solo puedes renovar suscripciones vencidas.');
            }

            // 2. Cálculo de nuevas fechas
            // El nuevo periodo inicia hoy mismo
            $inicio = now()->startOfDay();
            // Calcula cuándo vencerá según el plan contratado (mensual/anual)
            $venc   = Suscripcion::calcularVencimiento($inicio, $suscripcion->plan);

            // 3. Actualización en Base de Datos
            $suscripcion->update([
                'fecha_inicio'      => $inicio->toDateTimeString(),
                'fecha_vencimiento' => $venc->toDateTimeString(),
                'estado'            => 'activa', // Restaura el acceso al usuario
                'renovado'          => true,
            ]);

            return back()->with('success','Suscripción renovada.');
            
        } catch (\Throwable $e) {
            // 4. Manejo de errores internos
            Log::error('Error al renovar suscripción', ['e' => $e]);
            $msg = app()->environment('local') ? $e->getMessage() : 'No se pudo renovar.';
            return back()->withErrors($msg);
        }
    }
}
