<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use App\Models\Producto;
use App\Models\Proveedor;

class CompraController extends Controller
{
    public function __construct()
    {
        // vacío como pediste
    }

    /* =========================
     * INDEX
     * =========================*/
    public function index(Request $request)
    {
        $user  = Auth::user();
        $isSA  = method_exists($user, 'hasRole') ? $user->hasRole('superadmin') : false;
        $empId = $user->id_empresa;

        $base = DB::table('compras as c')
            ->join('proveedores as p', 'p.id', '=', 'c.id_proveedor')
            ->select('c.*', 'p.nombre as proveedor')
            ->when(!$isSA, fn($q) => $q->where('c.id_empresa', $empId))
            ->when($request->filled('estatus'), fn($q) => $q->where('c.estatus', $request->estatus))
            ->when($request->filled('id_proveedor'), fn($q) => $q->where('c.id_proveedor', $request->id_proveedor))
            ->when($request->filled('fecha_inicio'), fn($q) => $q->whereDate('c.fecha_compra', '>=', $request->fecha_inicio))
            ->when($request->filled('fecha_fin'), fn($q) => $q->whereDate('c.fecha_compra', '<=', $request->fecha_fin))
            ->when($request->filled('q'), function($q) use ($request) {
                $txt = trim($request->q);
                $q->where(function($w) use ($txt) {
                    $w->where('c.observaciones', 'like', "%{$txt}%")
                      ->orWhere('c.id', $txt)
                      ->orWhere('c.total', $txt);
                });
            });

        $today     = now()->toDateString();
        $monthFrom = now()->startOfMonth()->toDateString();
        $kpis = [
            'compras_hoy' => (clone $base)->whereDate('c.fecha_compra', $today)->sum('c.total'),
            'compras_mes' => (clone $base)->whereDate('c.fecha_compra', '>=', $monthFrom)->sum('c.total'),
            'conteo'      => (clone $base)->count(),
            'pendientes'  => (clone $base)->where('c.estatus','orden_compra')->count(),
        ];

        $compras = (clone $base)->orderByDesc('c.id')->paginate(15)->appends($request->query());

        $proveedores = $isSA
            ? Proveedor::orderBy('nombre')->get()
            : Proveedor::where('id_empresa', $empId)->orderBy('nombre')->get();

        return view('compras.index', compact('compras','kpis','proveedores'));
    }

    /* =========================
     * CREATE
     * =========================*/
    public function create(Request $request)
    {
        $user = Auth::user();
        $isSA = method_exists($user, 'hasRole') ? $user->hasRole('superadmin') : false;

        if ($isSA) {
            $proveedores = Proveedor::orderBy('nombre')->get();
            $productos   = Producto::orderBy('nombre')->get();
        } else {
            $empresaId   = $user->id_empresa;
            $proveedores = Proveedor::where('id_empresa', $empresaId)->orderBy('nombre')->get();
            $productos   = Producto::where('id_empresa', $empresaId)->orderBy('nombre')->get();
        }

        // Prefill opcional
        $prefill = [];
        if ($request->filled('producto_id')) {
            $p = $productos->firstWhere('id', (int)$request->producto_id);
            if ($p) {
                $prefill[] = [
                    'id_producto'    => $p->id,
                    'nombre'         => $p->nombre,
                    'cantidad'       => (float)($request->input('cantidad', 1)),
                    // costo será automático desde pivot
                    'costo_unitario' => null,
                    'descuento'      => 0,
                ];
            }
        }

        return view('compras.create', compact('proveedores','productos','prefill'));
    }

    /* =========================
     * STORE
     * =========================*/
    public function store(Request $request)
    {
        $user   = Auth::user();
        $isSA   = method_exists($user, 'hasRole') ? $user->hasRole('superadmin') : false;

        $validated = $request->validate([
            'id_proveedor'   => ['required','integer','exists:proveedores,id'],
            'fecha_compra'   => ['required','date'],
            'estatus'        => ['required', Rule::in(['borrador','orden_compra','recibida','cancelada'])],
            'observaciones'  => ['nullable','string'],
            'items'          => ['required','array','min:1'],
            'items.*.id_producto'    => ['required','integer','exists:productos,id'],
            'items.*.cantidad'       => ['required','numeric','gt:0'],
            // OJO: costo no viene del form, se obtiene del pivot → no validar desde request
            'items.*.descuento'      => ['nullable','numeric','min:0'],
        ]);

        $prov = Proveedor::findOrFail($validated['id_proveedor']);
        $empresaEfectiva = $isSA ? $prov->id_empresa : $user->id_empresa;

        if (!$isSA && $prov->id_empresa !== $empresaEfectiva) {
            return back()->withErrors('El proveedor no pertenece a tu empresa.')->withInput();
        }

        // Validar que los productos sean de la empresa (básico)
        $idsProductos = collect($validated['items'])->pluck('id_producto')->all();
        $idsOk = Producto::whereIn('id', $idsProductos)->pluck('id','id')->toArray();
        $noPertenecen = array_diff($idsProductos, array_keys($idsOk));
        if (!empty($noPertenecen)) {
            return back()->withErrors('Hay productos que no pertenecen a la empresa.')->withInput();
        }

        try {
            DB::beginTransaction();

            // Preparar items consultando costo desde pivot del proveedor elegido
            [$rows, $subtotal, $iva, $total] = $this->prepararItemsConProveedor(
                $validated['items'],
                (int)$validated['id_proveedor']
            );

            $compraId = DB::table('compras')->insertGetId([
                'id_empresa'   => $empresaEfectiva,
                'id_proveedor' => $validated['id_proveedor'],
                'id_usuario'   => $user->id,
                'fecha_compra' => $validated['fecha_compra'],
                'subtotal'     => $subtotal,
                'iva'          => $iva,
                'total'        => $total,
                'estatus'      => $validated['estatus'],
                'observaciones'=> $validated['observaciones'] ?? null,
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);

            foreach ($rows as $r) {
                DB::table('detalle_compras')->insert([
                    'id_compra'     => $compraId,
                    'id_producto'   => $r['id_producto'],
                    'cantidad'      => $r['cantidad'],
                    'costo_unitario'=> $r['costo_unitario'],
                    'descuento'     => $r['descuento'],
                    'total_linea'   => $r['total_linea'],
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);
            }

            if ($validated['estatus'] === 'recibida') {
                $this->sumarStock($compraId);
            }

            DB::commit();
            return redirect()->route('compras.show', $compraId)->with('success','Compra creada correctamente.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error STORE compra', ['e'=>$e->getMessage()]);
            return back()->withErrors('Ocurrió un error al guardar la compra.')->withInput();
        }
    }

    /* =========================
     * SHOW
     * =========================*/
    public function show($id)
    {
        $user  = Auth::user();
        $isSA  = method_exists($user, 'hasRole') ? $user->hasRole('superadmin') : false;
        $empId = $user->id_empresa;

        $compra = DB::table('compras as c')
            ->leftJoin('proveedores as p','p.id','=','c.id_proveedor')
            ->select('c.*','p.nombre as proveedor')
            ->where('c.id', $id)
            ->when(!$isSA, fn($q) => $q->where('c.id_empresa', $empId))
            ->first();

        if (!$compra) return redirect()->route('compras.index')->withErrors('Compra no encontrada.');

        $detalles = DB::table('detalle_compras as d')
            ->join('productos as pr','pr.id','=','d.id_producto')
            ->select('d.*','pr.nombre as producto','pr.sku')
            ->where('d.id_compra', $id)
            ->get();

        return view('compras.show', compact('compra','detalles'));
    }

    /* =========================
     * EDIT
     * =========================*/
    public function edit($id)
    {
        $user = Auth::user();
        $isSA = method_exists($user, 'hasRole') ? $user->hasRole('superadmin') : false;

        $compra = DB::table('compras')->where('id', $id)->first();
        if (!$compra) return redirect()->route('compras.index')->withErrors('Compra no encontrada.');
        if (!$isSA && (int)$compra->id_empresa !== (int)$user->id_empresa) {
            return redirect()->route('compras.index')->withErrors('No puedes editar compras de otra empresa.');
        }
        if ($compra->estatus === 'cancelada') {
            return back()->withErrors('No se puede editar una compra cancelada.');
        }

        if ($isSA) {
            $proveedores = Proveedor::orderBy('nombre')->get();
            $productos   = Producto::orderBy('nombre')->get();
        } else {
            $proveedores = Proveedor::where('id_empresa', $user->id_empresa)->orderBy('nombre')->get();
            $productos   = Producto::where('id_empresa', $user->id_empresa)->orderBy('nombre')->get();
        }

        $detalles = DB::table('detalle_compras as d')
            ->join('productos as pr','pr.id','=','d.id_producto')
            ->select('d.*','pr.nombre as producto')
            ->where('d.id_compra', $id)
            ->get();

        $prefill = $detalles->map(function($d){
            return [
                'id_producto'    => $d->id_producto,
                'nombre'         => $d->producto,
                'cantidad'       => (float)$d->cantidad,
                // en edit la UI volverá a cargar costo automático al elegir proveedor/producto
                'costo_unitario' => (float)$d->costo_unitario,
                'descuento'      => (float)($d->descuento ?? 0),
            ];
        })->values()->all();

        return view('compras.edit', compact('compra','proveedores','productos','prefill'));
    }

    /* =========================
     * UPDATE
     * =========================*/
    public function update(Request $request, $id)
    {
        $user  = Auth::user();
        $isSA  = method_exists($user, 'hasRole') ? $user->hasRole('superadmin') : false;
        $empId = $user->id_empresa;

        $compra = DB::table('compras')->where('id',$id)->first();
        if (!$compra) return redirect()->route('compras.index')->withErrors('Compra no encontrada.');
        if (!$isSA && (int)$compra->id_empresa !== (int)$empId) {
            return redirect()->route('compras.index')->withErrors('No puedes editar compras de otra empresa.');
        }
        if ($compra->estatus === 'cancelada') {
            return back()->withErrors('No se puede editar una compra cancelada.');
        }

        $validated = $request->validate([
            'id_proveedor'   => ['required','integer','exists:proveedores,id'],
            'fecha_compra'   => ['required','date'],
            'estatus'        => ['required', Rule::in(['borrador','orden_compra','recibida','cancelada'])],
            'observaciones'  => ['nullable','string'],
            'items'          => ['required','array','min:1'],
            'items.*.id_producto'    => ['required','integer','exists:productos,id'],
            'items.*.cantidad'       => ['required','numeric','gt:0'],
            'items.*.descuento'      => ['nullable','numeric','min:0'],
        ]);

        $prov = Proveedor::findOrFail($validated['id_proveedor']);
        if ((int)$prov->id_empresa !== (int)$compra->id_empresa) {
            return back()->withErrors('El proveedor no pertenece a la empresa de la compra.')->withInput();
        }

        try {
            DB::beginTransaction();

            $estabaRecibida = $compra->estatus === 'recibida';
            if ($estabaRecibida) {
                $this->revertirStock($compra->id);
            }

            // Recalcular líneas usando costo del proveedor elegido
            [$rows, $subtotal, $iva, $total] = $this->prepararItemsConProveedor(
                $validated['items'],
                (int)$validated['id_proveedor']
            );

            DB::table('detalle_compras')->where('id_compra',$compra->id)->delete();

            foreach ($rows as $r) {
                DB::table('detalle_compras')->insert([
                    'id_compra'     => $compra->id,
                    'id_producto'   => $r['id_producto'],
                    'cantidad'      => $r['cantidad'],
                    'costo_unitario'=> $r['costo_unitario'],
                    'descuento'     => $r['descuento'],
                    'total_linea'   => $r['total_linea'],
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);
            }

            DB::table('compras')->where('id',$compra->id)->update([
                'id_proveedor' => $validated['id_proveedor'],
                'fecha_compra' => $validated['fecha_compra'],
                'subtotal'     => $subtotal,
                'iva'          => $iva,
                'total'        => $total,
                'estatus'      => $validated['estatus'],
                'observaciones'=> $validated['observaciones'] ?? null,
                'updated_at'   => now(),
            ]);

            if ($validated['estatus'] === 'recibida') {
                $this->sumarStock($compra->id);
            }

            DB::commit();
            return redirect()->route('compras.show', $compra->id)->with('success','Compra actualizada correctamente.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error UPDATE compra', ['e'=>$e->getMessage()]);
            return back()->withErrors('Ocurrió un error al actualizar la compra.')->withInput();
        }
    }

    /* =========================
     * RECIBIR
     * =========================*/
    public function recibir($id)
    {
        $user  = Auth::user();
        $isSA  = method_exists($user, 'hasRole') ? $user->hasRole('superadmin') : false;
        $empId = $user->id_empresa;

        $compra = DB::table('compras')->where('id',$id)
            ->when(!$isSA, fn($q)=>$q->where('id_empresa',$empId))
            ->first();

        if (!$compra) return redirect()->route('compras.index')->withErrors('Compra no encontrada.');
        if (in_array($compra->estatus, ['cancelada','recibida'])) {
            return back()->withErrors('La compra no puede cambiar a recibida.');
        }

        try {
            DB::beginTransaction();

            DB::table('compras')->where('id',$compra->id)->update([
                'estatus'    => 'recibida',
                'updated_at' => now(),
            ]);

            $this->sumarStock($compra->id);

            DB::commit();
            return back()->with('success','Compra marcada como recibida y stock actualizado.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error RECIBIR compra', ['e'=>$e->getMessage()]);
            return back()->withErrors('Ocurrió un error al recibir la compra.');
        }
    }

    /* =========================
     * DESTROY
     * =========================*/
    public function destroy($id)
    {
        $user  = Auth::user();
        $isSA  = method_exists($user, 'hasRole') ? $user->hasRole('superadmin') : false;
        $empId = $user->id_empresa;

        $compra = DB::table('compras')->where('id',$id)
            ->when(!$isSA, fn($q)=>$q->where('id_empresa',$empId))
            ->first();

        if (!$compra) return redirect()->route('compras.index')->withErrors('Compra no encontrada.');

        try {
            DB::beginTransaction();

            if ($compra->estatus === 'recibida') {
                $this->revertirStock($compra->id);
            }

            DB::table('detalle_compras')->where('id_compra',$compra->id)->delete();
            DB::table('compras')->where('id',$compra->id)->delete();

            DB::commit();
            return redirect()->route('compras.index')->with('success','Compra eliminada.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error DESTROY compra', ['e'=>$e->getMessage()]);
            return back()->withErrors('No se pudo eliminar la compra.');
        }
    }

    /* =========================
     * JSON: Proveedores por producto (comparador)
     * =========================*/
    public function proveedoresProducto(Producto $producto)
    {
        $user = Auth::user();
        $isSA = method_exists($user, 'hasRole') ? $user->hasRole('superadmin') : false;

        if (!$isSA && (int)$producto->id_empresa !== (int)$user->id_empresa) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $producto->loadMissing('proveedores');

        $rows = $producto->proveedores
            ->map(function ($prov) {
                return [
                    'id'             => $prov->id,
                    'nombre'         => $prov->nombre,
                    'sku_proveedor'  => $prov->pivot->sku_proveedor,
                    'costo'          => (float)$prov->pivot->costo,
                    'moneda'         => $prov->pivot->moneda,
                    'lead_time_dias' => (int)($prov->pivot->lead_time_dias ?? 0),
                    'moq'            => (int)($prov->pivot->moq ?? 1),
                    'preferido'      => (bool)$prov->pivot->preferido,
                    'activo'         => (bool)$prov->pivot->activo,
                ];
            })
            ->sortBy([
                ['costo', 'asc'],
                ['preferido', 'desc'],
                ['lead_time_dias', 'asc'],
            ])
            ->values()
            ->all();

        return response()->json(['data' => $rows]);
    }

    /* =========================
     * JSON: costo del producto para proveedor
     * =========================*/
    public function costoProductoProveedor(Producto $producto, Proveedor $proveedor)
    {
        $user = Auth::user();
        $isSA = method_exists($user, 'hasRole') ? $user->hasRole('superadmin') : false;

        if (!$isSA && (int)$producto->id_empresa !== (int)$user->id_empresa) {
            return response()->json(['error' => 'No autorizado'], 403);
        }
        if ((int)$proveedor->id_empresa !== (int)$producto->id_empresa) {
            return response()->json(['error' => 'Proveedor y producto de distinta empresa'], 422);
        }

        $row = DB::table('producto_proveedor')
            ->where('producto_id', $producto->id)
            ->where('proveedor_id', $proveedor->id)
            ->first();

        if (!$row) {
            return response()->json(['error' => 'Sin costo definido para este proveedor'], 404);
        }

        return response()->json([
            'producto_id' => $producto->id,
            'proveedor_id'=> $proveedor->id,
            'costo'       => (float)$row->costo,
            'moneda'      => $row->moneda,
            'activo'      => (bool)$row->activo,
            'preferido'   => (bool)$row->preferido,
        ]);
    }

    /* =========================
     * Helpers de cálculo / stock
     * =========================*/
    private function prepararItemsConProveedor(array $items, int $proveedorId): array
    {
        $rows = [];
        $subtotal = 0;
        $faltan = [];

        foreach ($items as $it) {
            $productoId = (int)$it['id_producto'];
            $cantidad   = (float)$it['cantidad'];
            $desc       = isset($it['descuento']) ? (float)$it['descuento'] : 0;

            $pivot = DB::table('producto_proveedor')
                ->where('producto_id', $productoId)
                ->where('proveedor_id', $proveedorId)
                ->first();

            if (!$pivot) {
                $faltan[] = $productoId;
                continue;
            }

            $costo = (float)$pivot->costo;
            $linea = max(0, ($cantidad * $costo) - $desc);

            $rows[] = [
                'id_producto'    => $productoId,
                'cantidad'       => $cantidad,
                'costo_unitario' => round($costo, 2),
                'descuento'      => round($desc, 2),
                'total_linea'    => round($linea, 2),
            ];

            $subtotal += $linea;
        }

        if (!empty($faltan)) {
            // Reemplaza por nombres legibles si lo deseas (join a productos para mostrar nombres)
            $nombres = Producto::whereIn('id', $faltan)->pluck('nombre')->implode(', ');
            throw new \RuntimeException("Falta definir costo del proveedor para: {$nombres}");
        }

        $subtotal = round($subtotal, 2);
        $iva      = round($subtotal * 0.16, 2);
        $total    = round($subtotal + $iva, 2);

        return [$rows, $subtotal, $iva, $total];
    }

    private function sumarStock(int $compraId): void
    {
        $detalles = DB::table('detalle_compras')->where('id_compra',$compraId)->get(['id_producto','cantidad']);
        foreach ($detalles as $d) {
            DB::table('productos')->where('id',$d->id_producto)->update([
                'stock' => DB::raw('stock + '.((float)$d->cantidad))
            ]);
        }
    }

    private function revertirStock(int $compraId): void
    {
        $detalles = DB::table('detalle_compras')->where('id_compra',$compraId)->get(['id_producto','cantidad']);
        foreach ($detalles as $d) {
            DB::table('productos')->where('id',$d->id_producto)->update([
                'stock' => DB::raw('GREATEST(0, stock - '.((float)$d->cantidad).')')
            ]);
        }
    }
}
