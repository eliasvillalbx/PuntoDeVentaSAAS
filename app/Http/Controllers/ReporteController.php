<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Empresa;
use App\Models\Suscripcion;
use App\Models\Producto;
use App\Models\Categoria;
use App\Models\Proveedor;

class ReporteController extends Controller
{
    /**
     * Helper para obtener la Empresa ID.
     * Si es usuario normal, retorna su ID.
     * Si es SuperAdmin, retorna el del request (filtro).
     */
    private function getEmpresaId(Request $request)
    {
        $user = auth()->user();
        if (!is_null($user->id_empresa)) {
            return $user->id_empresa;
        }
        return $request->input('empresa_id');
    }

    /**
     * Vista Principal con Gráficos y Filtros.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $isSA = is_null($user->id_empresa);
        $empresaId = $this->getEmpresaId($request);
        
        // Bandera para mostrar datos operativos
        $mostrarDatos = $empresaId ? true : false;

        // Datos para los selectores de filtro
        $categorias = [];
        $proveedores = [];
        if ($mostrarDatos) {
            $categorias = Categoria::where('id_empresa', $empresaId)->where('activa', true)->get();
            $proveedores = Proveedor::where('id_empresa', $empresaId)->where('activo', true)->get();
        }

        // --- MÉTRICAS SAAS (SOLO SA) ---
        $saasMetrics = [];
        if ($isSA) {
            $empresasStatus = DB::table('empresas')
                ->select('activa', DB::raw('count(*) as total'))
                ->groupBy('activa')
                ->get();
            
            $subsStatus = DB::table('suscripciones')
                ->select('estado', DB::raw('count(*) as total'))
                ->groupBy('estado')
                ->get();
            
            $saasMetrics = [
                'empresas_labels' => $empresasStatus->map(fn($e) => $e->activa ? 'Activas' : 'Inactivas'),
                'empresas_data' => $empresasStatus->pluck('total'),
                'subs_labels' => $subsStatus->pluck('estado'),
                'subs_data' => $subsStatus->pluck('total'),
            ];
        }

        // --- DATOS OPERATIVOS (GRÁFICOS) ---
        $rentabilidad = collect([]);
        $clientes = collect([]);
        $inventario = collect([]);
        $proveedoresGraph = collect([]);

        if ($mostrarDatos) {
            // 1. Rentabilidad (Top 5 por GANANCIA)
            // Primero obtenemos ventas y unidades por producto
            $productosRaw = DB::table('productos')
                ->leftJoin('detalle_ventas', 'productos.id', '=', 'detalle_ventas.producto_id')
                ->leftJoin('ventas', 'ventas.id', '=', 'detalle_ventas.venta_id')
                ->where('productos.id_empresa', $empresaId)
                ->select('productos.id', 'productos.nombre', 'productos.costo_referencial')
                ->selectRaw("COALESCE(SUM(CASE WHEN ventas.estatus = 'facturada' THEN detalle_ventas.total_linea ELSE 0 END), 0) as ventas")
                ->selectRaw("COALESCE(SUM(CASE WHEN ventas.estatus = 'facturada' THEN detalle_ventas.cantidad ELSE 0 END), 0) as unidades")
                ->groupBy('productos.id', 'productos.nombre', 'productos.costo_referencial')
                ->get();

            // Calculamos Ganancia Real (Venta - Costo Real)
            $rentabilidad = $productosRaw->map(function($p) use ($empresaId) {
                // Lógica de costo (Cascada)
                $costo = $p->costo_referencial ?? 0;
                
                // Buscar en historial compras
                $infoCompras = DB::table('detalle_compras')
                    ->join('compras', 'compras.id', '=', 'detalle_compras.id_compra')
                    ->where('detalle_compras.id_producto', $p->id)
                    ->where('compras.id_empresa', $empresaId)
                    ->where('compras.estatus', 'recibida')
                    ->selectRaw('SUM(detalle_compras.cantidad) as total_unidades')
                    ->selectRaw('SUM(detalle_compras.total_linea) as costo_total')
                    ->first();

                if ($infoCompras && $infoCompras->total_unidades > 0) {
                    $costo = $infoCompras->costo_total / $infoCompras->total_unidades;
                } elseif ($costo <= 0) {
                    // Buscar en proveedores
                    $promedioProv = DB::table('producto_proveedor')
                        ->where('producto_id', $p->id)->where('activo', true)->avg('costo');
                    if ($promedioProv > 0) $costo = $promedioProv;
                }

                // Ganancia
                $p->ganancia = $p->ventas - ($p->unidades * $costo);
                return $p;
            })->sortByDesc('ganancia')->take(5)->values();

            // 2. Clientes (Top 5 por Volumen)
            $clientes = DB::table('clientes')
                ->join('ventas', 'clientes.id', '=', 'ventas.cliente_id')
                ->where('clientes.empresa_id', $empresaId)
                ->where('ventas.estatus', 'facturada')
                ->selectRaw("CONCAT(clientes.nombre, ' ', COALESCE(clientes.apellido_paterno,'')) as nombre_completo")
                ->selectRaw('SUM(ventas.total) as total')
                ->groupBy('clientes.id', 'clientes.nombre', 'clientes.apellido_paterno')
                ->orderByDesc('total')->take(5)->get();

            // 3. Inventario (Valorizado con Costo Real)
            $allProds = Producto::with('categoria')->where('id_empresa', $empresaId)->get();
            $inventarioGrouped = $allProds->map(function($p) use ($empresaId) {
                // Determinar costo
                $costo = $p->costo_referencial;
                // Si es 0, buscamos historial
                if ($costo <= 0) {
                     $infoCompras = DB::table('detalle_compras')
                        ->join('compras', 'compras.id', '=', 'detalle_compras.id_compra')
                        ->where('detalle_compras.id_producto', $p->id)
                        ->where('compras.id_empresa', $empresaId)
                        ->where('compras.estatus', 'recibida')
                        ->selectRaw('SUM(detalle_compras.cantidad) as total_unidades, SUM(detalle_compras.total_linea) as costo_total')
                        ->first();
                     if ($infoCompras && $infoCompras->total_unidades > 0) {
                         $costo = $infoCompras->costo_total / $infoCompras->total_unidades;
                     } else {
                         // Si no hay historial, proveedores
                         $promedioProv = DB::table('producto_proveedor')->where('producto_id', $p->id)->where('activo', true)->avg('costo');
                         if ($promedioProv > 0) $costo = $promedioProv;
                     }
                }
                
                return [
                    'cat' => $p->categoria ? $p->categoria->nombre : 'Sin Categoría',
                    'val' => $p->stock * ($costo ?? 0)
                ];
            })->groupBy('cat')->map(fn($g) => $g->sum('val'));

            // Formatear para ChartJS
            $inventario = $inventarioGrouped->map(fn($val, $key) => (object)['nombre_categoria' => $key, 'valor' => $val])->values();

            // 4. Proveedores (Top 5 Gasto)
            $proveedoresGraph = DB::table('proveedores')
                ->join('compras', 'proveedores.id', '=', 'compras.id_proveedor')
                ->where('compras.id_empresa', $empresaId)
                ->where('compras.estatus', 'recibida')
                ->select('proveedores.nombre', DB::raw('SUM(compras.total) as total'))
                ->groupBy('proveedores.id', 'proveedores.nombre')
                ->orderByDesc('total')->take(5)->get();
        }

        return view('reportes.index', [
            'isSA' => $isSA,
            'empresas' => $isSA ? Empresa::where('activa', true)->get() : [],
            'empresaIdSeleccionada' => $empresaId,
            'mostrarDatos' => $mostrarDatos,
            'categorias' => $categorias,
            'listaProveedores' => $proveedores,
            'saasMetrics' => $saasMetrics,
            'rentabilidad' => $rentabilidad,
            'clientes' => $clientes,
            'inventario' => $inventario,
            'proveedores' => $proveedoresGraph,
        ]);
    }

    /* =========================================================================
     * FN.16: RENTABILIDAD (CASCADA DE COSTOS)
     * ========================================================================= */
    public function rentabilidad(Request $request)
    {
        $empresaId = $this->getEmpresaId($request);
        $empresa = Empresa::find($empresaId);

        $fechaInicio = $request->input('fecha_inicio');
        $fechaFin    = $request->input('fecha_fin');
        $categoriaId = $request->input('categoria_id');
        $margenMin   = $request->input('margen_minimo');

        $query = DB::table('productos')
            ->leftJoin('detalle_ventas', 'productos.id', '=', 'detalle_ventas.producto_id')
            ->leftJoin('ventas', 'ventas.id', '=', 'detalle_ventas.venta_id')
            ->where('productos.id_empresa', $empresaId);

        if ($categoriaId) {
            $query->where('productos.categoria_id', $categoriaId);
        }
        
        if ($fechaInicio) {
            $query->where(function($q) use ($fechaInicio) {
                $q->whereDate('ventas.fecha_venta', '>=', $fechaInicio)->orWhereNull('ventas.id');
            });
        }
        if ($fechaFin) {
            $query->where(function($q) use ($fechaFin) {
                $q->whereDate('ventas.fecha_venta', '<=', $fechaFin)->orWhereNull('ventas.id');
            });
        }

        $productos = $query->select(
                'productos.id', 'productos.nombre', 'productos.sku', 'productos.costo_referencial',
                DB::raw("COALESCE(SUM(CASE WHEN ventas.estatus = 'facturada' THEN detalle_ventas.cantidad ELSE 0 END), 0) as unidades_vendidas"),
                DB::raw("COALESCE(SUM(CASE WHEN ventas.estatus = 'facturada' THEN detalle_ventas.total_linea ELSE 0 END), 0) as ingresos_totales")
            )
            ->groupBy('productos.id', 'productos.nombre', 'productos.sku', 'productos.costo_referencial')
            ->get()
            ->map(function($p) use ($empresaId) {
                
                // 1. Historial Compras
                $infoCompras = DB::table('detalle_compras')
                    ->join('compras', 'compras.id', '=', 'detalle_compras.id_compra')
                    ->where('detalle_compras.id_producto', $p->id)
                    ->where('compras.id_empresa', $empresaId)
                    ->where('compras.estatus', 'recibida')
                    ->selectRaw('SUM(detalle_compras.cantidad) as total_unidades')
                    ->selectRaw('SUM(detalle_compras.total_linea) as costo_total')
                    ->first();

                if ($infoCompras && $infoCompras->total_unidades > 0) {
                    $p->costo_calculado = $infoCompras->costo_total / $infoCompras->total_unidades;
                    $p->origen_costo = 'Promedio Compras';
                } else {
                    // 2. Media Proveedores
                    $promedioProveedores = DB::table('producto_proveedor')
                        ->where('producto_id', $p->id)
                        ->where('activo', true)
                        ->avg('costo');

                    if ($promedioProveedores > 0) {
                        $p->costo_calculado = $promedioProveedores;
                        $p->origen_costo = 'Media Proveedores';
                    } else {
                        // 3. Referencial
                        $p->costo_calculado = $p->costo_referencial ?? 0;
                        $p->origen_costo = 'Referencial';
                    }
                }

                $costoVendido = $p->unidades_vendidas * $p->costo_calculado;
                $p->ganancia_neta = $p->ingresos_totales - $costoVendido;
                
                // Evitar división por cero
                $p->margen = $p->ingresos_totales > 0 
                    ? ($p->ganancia_neta / $p->ingresos_totales) * 100 
                    : 0;

                return $p;
            });

        if ($margenMin) {
            $productos = $productos->filter(fn($p) => $p->margen >= $margenMin);
        }

        $productos = $productos->sortByDesc('ganancia_neta');

        $pdf = Pdf::loadView('reportes.pdf.rentabilidad', compact('productos', 'empresa', 'fechaInicio', 'fechaFin'));
        return $pdf->download('rentabilidad_real.pdf');
    }

    /* =========================================================================
     * FN.17: SUSCRIPCIONES (CON PRECIOS DE CONFIG)
     * ========================================================================= */
    public function suscripciones(Request $request) 
    {
        if (!is_null(auth()->user()->id_empresa)) abort(403);

        $estado = $request->input('estado');
        $query = Suscripcion::with('empresa');
        
        if ($estado) {
            $query->where('estado', $estado);
        }
        
        $suscripciones = $query->orderBy('fecha_vencimiento', 'asc')->get();

        // Configuración de Clip
        $planesConfig = Config::get('clip.plans', []);
        
        $mrr = 0;

        $suscripciones->transform(function($sub) use ($planesConfig, &$mrr) {
            $precio = 0;
            $divisorMRR = 1; // Por defecto mensual

            switch ($sub->plan) {
                case '1_mes':
                    $precio = $planesConfig['mensual']['amount'] ?? 0; 
                    $divisorMRR = 1; 
                    break;
                case '6_meses':
                    $precio = ($planesConfig['trimestral']['amount'] ?? 0) * 2; 
                    $divisorMRR = 6; 
                    break;
                case '1_año':
                    $precio = $planesConfig['anual']['amount'] ?? 0; 
                    $divisorMRR = 12; 
                    break;
                case '3_años':
                    $precio = ($planesConfig['anual']['amount'] ?? 0) * 3; 
                    $divisorMRR = 36; 
                    break;
                default: 
                    $precio = 0;
            }

            $sub->precio_calculado = $precio;

            if ($sub->estado === 'activa') {
                $mrr += ($precio / $divisorMRR);
            }
            return $sub;
        });

        $activas = Empresa::where('activa', true)->count();
        $inactivas = Empresa::where('activa', false)->count();

        $pdf = Pdf::loadView('reportes.pdf.suscripciones', compact('suscripciones', 'activas', 'inactivas', 'mrr', 'estado'));
        return $pdf->download('reporte_suscripciones_saas.pdf');
    }

    /* =========================================================================
     * FN.18: CLIENTES
     * ========================================================================= */
    public function clientes(Request $request) 
    {
        $empresaId = $this->getEmpresaId($request);
        $empresa = Empresa::find($empresaId);
        $montoMin = $request->input('monto_minimo');
        $fechaInicio = $request->input('fecha_inicio');
        $fechaFin = $request->input('fecha_fin');

        $query = DB::table('clientes')
            ->join('ventas', 'clientes.id', '=', 'ventas.cliente_id')
            ->where('clientes.empresa_id', $empresaId)
            ->where('ventas.estatus', 'facturada');

        if ($fechaInicio) $query->whereDate('ventas.fecha_venta', '>=', $fechaInicio);
        if ($fechaFin) $query->whereDate('ventas.fecha_venta', '<=', $fechaFin);

        $query->select('clientes.nombre', 'clientes.razon_social', 'clientes.apellido_paterno',
            DB::raw('COUNT(ventas.id) as frecuencia_compra'),
            DB::raw('SUM(ventas.total) as total_gastado'),
            DB::raw('AVG(ventas.total) as ticket_promedio'))
            ->groupBy('clientes.id', 'clientes.nombre', 'clientes.razon_social', 'clientes.apellido_paterno');

        if ($montoMin) {
            $query->having('total_gastado', '>=', $montoMin);
        }

        $clientes = $query->orderByDesc('total_gastado')->get();
        $pdf = Pdf::loadView('reportes.pdf.clientes', compact('clientes', 'empresa', 'fechaInicio', 'fechaFin'));
        return $pdf->download('clientes.pdf');
    }

    /* =========================================================================
     * FN.19: MOVIMIENTO DE INVENTARIO (CASCADA DE COSTOS)
     * ========================================================================= */
    public function movimientoInventario(Request $request) 
    {
        $empresaId = $this->getEmpresaId($request);
        $empresa = Empresa::find($empresaId);

        $catId = $request->input('categoria_id');
        $stockBajo = $request->input('stock_bajo');

        $query = Producto::with('categoria')->where('id_empresa', $empresaId);

        if ($catId) $query->where('categoria_id', $catId);
        
        if (isset($stockBajo) && $stockBajo !== '') {
            $nivel = is_numeric($stockBajo) ? $stockBajo : 5;
            $query->where('stock', '<=', $nivel);
        }

        // Procesamos para asignar costo real
        $inventario = $query->orderBy('stock', 'asc')->get()->map(function($p) use ($empresaId) {
            
            // 1. Historial
            $infoCompras = DB::table('detalle_compras')
                ->join('compras', 'compras.id', '=', 'detalle_compras.id_compra')
                ->where('detalle_compras.id_producto', $p->id)
                ->where('compras.id_empresa', $empresaId)
                ->where('compras.estatus', 'recibida')
                ->selectRaw('SUM(detalle_compras.cantidad) as total_unidades')
                ->selectRaw('SUM(detalle_compras.total_linea) as costo_total')
                ->first();

            if ($infoCompras && $infoCompras->total_unidades > 0) {
                $p->costo_calculado = $infoCompras->costo_total / $infoCompras->total_unidades;
                $p->origen_costo = 'Promedio Compras';
            } else {
                // 2. Proveedores
                $promedioProveedores = DB::table('producto_proveedor')
                    ->where('producto_id', $p->id)
                    ->where('activo', true)
                    ->avg('costo');

                if ($promedioProveedores > 0) {
                    $p->costo_calculado = $promedioProveedores;
                    $p->origen_costo = 'Media Proveedores';
                } else {
                    // 3. Referencial
                    $p->costo_calculado = $p->costo_referencial ?? 0;
                    $p->origen_costo = 'Referencial';
                }
            }

            $p->valor_stock_real = $p->stock * $p->costo_calculado;
            return $p;
        });

        $valorTotal = $inventario->sum('valor_stock_real');
        
        $pdf = Pdf::loadView('reportes.pdf.inventario', compact('inventario', 'valorTotal', 'empresa', 'stockBajo'));
        return $pdf->download('inventario_valorizado.pdf');
    }

    /* =========================================================================
     * FN.20: PROVEEDORES
     * ========================================================================= */
    public function proveedores(Request $request) 
    {
        $empresaId = $this->getEmpresaId($request);
        $empresa = Empresa::find($empresaId);
        $provId = $request->input('proveedor_id');
        $fechaInicio = $request->input('fecha_inicio');
        $fechaFin = $request->input('fecha_fin');

        $query = DB::table('proveedores')
            ->join('compras', 'proveedores.id', '=', 'compras.id_proveedor')
            ->where('compras.id_empresa', $empresaId)
            ->where('compras.estatus', 'recibida');

        if ($fechaInicio) $query->whereDate('compras.fecha_compra', '>=', $fechaInicio);
        if ($fechaFin) $query->whereDate('compras.fecha_compra', '<=', $fechaFin);
        if ($provId) $query->where('proveedores.id', $provId);

        $proveedores = $query->select('proveedores.nombre', 'proveedores.contacto', 'proveedores.telefono',
            DB::raw('COUNT(compras.id) as cantidad_compras'),
            DB::raw('SUM(compras.total) as total_comprado'))
            ->groupBy('proveedores.id', 'proveedores.nombre', 'proveedores.contacto', 'proveedores.telefono')
            ->orderByDesc('total_comprado')->get();
            
        $pdf = Pdf::loadView('reportes.pdf.proveedores', compact('proveedores', 'empresa', 'fechaInicio', 'fechaFin'));
        return $pdf->download('proveedores.pdf');
    }
}