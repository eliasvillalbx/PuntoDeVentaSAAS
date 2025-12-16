<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Suscripcion;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // 1. Validar permiso (puedes usar middleware, pero por seguridad aquí también)
        // if (!auth()->user()->hasRole('superadmin')) { abort(403); }

        // 2. Filtros
        $estado = $request->get('estado');
        $plan = $request->get('plan');
        $fechaInicio = $request->get('fecha_inicio');
        $fechaFin = $request->get('fecha_fin');

        // 3. Consulta Base con Eager Loading
        $query = Suscripcion::with('empresa');

        if ($estado) {
            $query->where('estado', $estado);
        }
        if ($plan) {
            $query->where('plan', $plan);
        }
        if ($fechaInicio && $fechaFin) {
            $query->whereBetween('fecha_inicio', [$fechaInicio, $fechaFin]);
        }

        // Clonamos la query para no afectar los contadores globales si no queremos filtros en los KPIs
        // O usamos la query filtrada para que los KPIs respondan a los filtros (recomendado)
        $suscripciones = $query->get();

        // 4. Cálculo de KPIs
        $totalEmpresas = $suscripciones->unique('empresa_id')->count();
        $activas = $suscripciones->where('estado', 'activa')->count();
        $inactivas = $suscripciones->where('estado', '!=', 'activa')->count(); // O vencida/cancelada

        // 5. Cálculo de MRR (Estimado)
        // Definimos precios base (esto debería venir de BD idealmente)
        $precios = [
            'mensual' => 500,     // $500/mes
            'trimestral' => 1400, // $1400/3 meses (~$466 MRR)
            'anual' => 5000       // $5000/12 meses (~$416 MRR)
        ];

        $mrr = 0;
        foreach ($suscripciones->where('estado', 'activa') as $sub) {
            $precio = $precios[strtolower($sub->plan)] ?? 0;
            $divisor = match(strtolower($sub->plan)) {
                'trimestral' => 3,
                'anual' => 12,
                default => 1
            };
            $mrr += ($precio / $divisor);
        }

        // 6. Datos para Gráficas (Chart.js)
        // Gráfica 1: Distribución por Plan
        $planesData = $suscripciones->groupBy('plan')->map->count();
        
        // Gráfica 2: Estado de Suscripciones
        $estadosData = $suscripciones->groupBy('estado')->map->count();

        return view('dashboard', compact(
            'suscripciones', 
            'totalEmpresas', 
            'activas', 
            'inactivas', 
            'mrr',
            'planesData',
            'estadosData'
        ));
    }
    
    // Métodos para exportar (lógica simplificada)
    public function exportarExcel(Request $request) {
        // Aquí llamarías a tu clase de exportación de Maatwebsite
        return back()->with('status', 'Función de Excel pendiente de configurar Class Export'); 
    }

    public function exportarPdf(Request $request) {
        // Aquí llamarías a DomPDF cargando una vista limpia
        return back()->with('status', 'Función de PDF pendiente de configurar Vista PDF');
    }
}