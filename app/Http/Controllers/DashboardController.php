<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

use App\Models\Venta;
use App\Models\Compra;
use App\Models\Cliente;
use App\Models\Producto;
use App\Models\Suscripcion;
use App\Models\Empresa;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $empresaId = auth()->user()->id_empresa;
        $isSA = is_null($empresaId); // Helper para saber si es SuperAdmin
        $now = Carbon::now();

        // =========================================================================
        // 1. KPI FINANCIEROS (Mes Actual)
        // =========================================================================
        $ventasMes = Venta::query()
            ->when($empresaId, fn($q) => $q->where('empresa_id', $empresaId))
            ->where('estatus', 'pagada')
            ->whereMonth('fecha_venta', $now->month)
            ->whereYear('fecha_venta', $now->year)
            ->sum('total');

        $comprasMes = Compra::query()
            ->when($empresaId, fn($q) => $q->where('id_empresa', $empresaId))
            ->whereIn('estatus', ['recibida', 'pagada', 'finalizada'])
            ->whereMonth('fecha_compra', $now->month)
            ->whereYear('fecha_compra', $now->year)
            ->sum('total');

        $balance = $ventasMes - $comprasMes;

        // =========================================================================
        // 2. OPERATIVIDAD & INVENTARIO (FN.19)
        // =========================================================================
        $ventasHoy = Venta::query()
            ->when($empresaId, fn($q) => $q->where('empresa_id', $empresaId))
            ->whereDate('fecha_venta', $now->today())
            ->where('estatus', 'pagada')
            ->count();

        $clientesNuevos = Cliente::query()
            ->when($empresaId, fn($q) => $q->where('empresa_id', $empresaId)) // Ajuste manual del where
            ->whereMonth('created_at', $now->month)
            ->count();

        // Cálculo del Valor del Inventario (Stock * Costo Referencial)
        $valorInventario = Producto::query()
            ->when($empresaId, fn($q) => $q->where('id_empresa', $empresaId))
            ->select(DB::raw('SUM(stock * costo_referencial) as total_valor'))
            ->value('total_valor') ?? 0;

        // =========================================================================
        // 3. SUSCRIPCIÓN (FN.17)
        // =========================================================================
        $suscripcion = null;
        $resumenSuscripciones = [];

        if ($empresaId) {
            // Usuario Normal: Ve su propia suscripción
            $suscripcion = Suscripcion::query()
                ->where('empresa_id', $empresaId)
                ->latest('fecha_vencimiento')
                ->first();
        } else {
            // Super Admin: Ve resumen global de empresas
            $resumenSuscripciones = [
                'activas' => Empresa::where('activa', true)->count(),
                'inactivas' => Empresa::where('activa', false)->count(),
                // Lista de las próximas a vencer (global)
                'proximas_vencer' => Suscripcion::with('empresa')
                    ->where('estado', 'activa')
                    ->whereDate('fecha_vencimiento', '<=', $now->copy()->addDays(7))
                    ->take(5)
                    ->get()
            ];
        }

        // =========================================================================
        // 4. TOP PRODUCTOS / RENTABILIDAD (FN.16)
        // =========================================================================
        $topProductos = DB::table('detalle_ventas')
            ->join('ventas', 'ventas.id', '=', 'detalle_ventas.venta_id')
            ->join('productos', 'productos.id', '=', 'detalle_ventas.producto_id')
            ->when($empresaId, fn($q) => $q->where('ventas.empresa_id', $empresaId))
            ->where('ventas.estatus', 'pagada')
            ->whereMonth('ventas.fecha_venta', $now->month)
            ->select(
                'productos.nombre',
                'productos.costo_referencial',
                DB::raw('SUM(detalle_ventas.cantidad) as total_vendido'),
                DB::raw('SUM(detalle_ventas.total_linea) as dinero_generado')
            )
            ->groupBy('productos.id', 'productos.nombre', 'productos.costo_referencial')
            ->orderByDesc('total_vendido')
            ->limit(5)
            ->get()
            ->map(function ($prod) {
                // Calculamos margen aproximado al vuelo
                $costoTotal = $prod->total_vendido * $prod->costo_referencial;
                $ganancia = $prod->dinero_generado - $costoTotal;
                $prod->ganancia_estimada = $ganancia;
                return $prod;
            });

        // =========================================================================
        // 5. ANÁLISIS DE CLIENTES (FN.18) - TOP 5 MEJORES CLIENTES
        // =========================================================================
        $topClientes = DB::table('ventas')
            ->join('clientes', 'clientes.id', '=', 'ventas.cliente_id')
            ->when($empresaId, fn($q) => $q->where('ventas.empresa_id', $empresaId))
            ->where('ventas.estatus', 'pagada')
            ->select(
                'clientes.nombre',
                'clientes.apellido_paterno',
                'clientes.razon_social',
                'clientes.tipo_persona',
                DB::raw('COUNT(ventas.id) as compras_realizadas'),
                DB::raw('SUM(ventas.total) as total_gastado')
            )
            ->groupBy('clientes.id', 'clientes.nombre', 'clientes.apellido_paterno', 'clientes.razon_social', 'clientes.tipo_persona')
            ->orderByDesc('total_gastado')
            ->limit(5)
            ->get();

        // =========================================================================
        // 6. COMPRAS A PROVEEDORES (FN.20) - TOP 5 PROVEEDORES
        // =========================================================================
        $topProveedores = DB::table('compras')
            ->join('proveedores', 'proveedores.id', '=', 'compras.id_proveedor')
            ->when($empresaId, fn($q) => $q->where('compras.id_empresa', $empresaId))
            ->whereIn('compras.estatus', ['recibida', 'pagada', 'finalizada'])
            ->select(
                'proveedores.nombre',
                DB::raw('COUNT(compras.id) as ordenes_hechas'),
                DB::raw('SUM(compras.total) as total_pagado')
            )
            ->groupBy('proveedores.id', 'proveedores.nombre')
            ->orderByDesc('total_pagado')
            ->limit(5)
            ->get();

        // =========================================================================
        // 7. ALERTAS STOCK & HISTORIAL
        // =========================================================================
        $stockBajo = Producto::query()
            ->when($empresaId, fn($q) => $q->where('id_empresa', $empresaId))
            ->where('stock', '<=', 5)
            ->take(6)
            ->get();

        $ultimasVentas = Venta::with(['cliente', 'usuario'])
            ->when($empresaId, fn($q) => $q->where('empresa_id', $empresaId))
            ->latest()
            ->take(5)
            ->get();

        return view('dashboard', compact(
            'isSA',
            'ventasMes', 'comprasMes', 'balance', 'valorInventario',
            'ventasHoy', 'clientesNuevos',
            'suscripcion', 'resumenSuscripciones',
            'topProductos', 'topClientes', 'topProveedores',
            'stockBajo', 'ultimasVentas'
        ));
    }
}