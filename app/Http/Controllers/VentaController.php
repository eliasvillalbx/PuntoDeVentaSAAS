<?php

namespace App\Http\Controllers;

use App\Models\Venta;
use App\Models\DetalleVenta;
use App\Models\Producto;
use App\Models\Empresa;
use App\Models\Cliente;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Barryvdh\DomPDF\Facade\Pdf; // PDF facade
use Throwable;

class VentaController extends Controller
{
    /* =========================================================================
     | INDEX – Lista filtrable + KPIs (día/mes)
     * ====================================================================== */
    public function index(Request $request)
    {
        try {
            $user      = $request->user();
            $empresaId = $this->resolveEmpresaIdForList($request, $user);

            $query = Venta::with([
                    'empresa:id,razon_social,nombre_comercial',
                    'usuario:id,nombre,apellido_paterno,apellido_materno',
                    'cliente:id,nombre,razon_social'
                ])
                ->where('empresa_id', $empresaId);

            if ($user->hasRole('vendedor')) {
                $query->where('usuario_id', $user->id);
            }

            // Filtros
            if (($estatus = (string) $request->get('estatus', '')) !== '') {
                $query->where('estatus', $estatus);
            }
            if ($vid = (int) $request->get('vendedor_id', 0)) {
                $query->where('usuario_id', $vid);
            }
            if ($cid = (int) $request->get('cliente_id', 0)) {
                $query->where('cliente_id', $cid);
            }
            if ($fini = $request->date('fecha_inicio')) {
                $query->where('fecha_venta', '>=', $fini->toDateString());
            }
            if ($ffin = $request->date('fecha_fin')) {
                $query->where('fecha_venta', '<=', $ffin->toDateString());
            }
            if ($q = trim((string) $request->get('q', ''))) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('observaciones', 'like', "%{$q}%")
                        ->orWhere('id', $q)
                        ->orWhere('total', $q);
                });
            }

            $ventas = $query->latest('fecha_venta')->latest('id')->paginate(15)->withQueryString();

            // KPIs
            $kpiQ = Venta::where('empresa_id', $empresaId);
            if ($user->hasRole('vendedor')) {
                $kpiQ->where('usuario_id', $user->id);
            }
            $hoy       = now()->toDateString();
            $inicioMes = now()->startOfMonth()->toDateString();

            $totales = [
                'hoy'      => (clone $kpiQ)->whereDate('fecha_venta', $hoy)->sum('total'),
                'mes'      => (clone $kpiQ)->whereBetween('fecha_venta', [$inicioMes, $hoy])->sum('total'),
                'conteo'   => (clone $kpiQ)->count(),
                'prefPend' => (clone $kpiQ)->where('estatus','prefactura')->count(),
            ];

            // Listas para filtros
            $vendedores = User::where('id_empresa', $empresaId)
                ->role(['vendedor','gerente','administrador_empresa','superadmin'])
                ->orderBy('nombre')
                ->get(['id','nombre','apellido_paterno','apellido_materno']);

            $clientes = Cliente::where('empresa_id', $empresaId)
                ->orderBy('nombre')
                ->get(['id','nombre','razon_social']);

            $empresas = $user->hasRole('superadmin')
                ? Empresa::orderBy('razon_social')->get(['id','razon_social','nombre_comercial'])
                : collect();

            return view('ventas.index', compact(
                'ventas','totales','empresaId','vendedores','clientes','empresas'
            ));
        } catch (Throwable $e) {
            Log::error('Ventas.index error', ['e' => $e]);
            return back()->withErrors('No se pudo cargar el listado de ventas.')->withInput();
        }
    }

    /* =========================================================================
     | CREATE – Formulario
     * ====================================================================== */
    public function create(Request $request)
    {
        $user   = $request->user();
        $isSA   = $user->hasRole('superadmin');

        $empresaId = $isSA
            ? (int) $request->get('empresa_id', 0)
            : (int) $user->id_empresa;

        $empresas = $isSA
            ? Empresa::orderBy('razon_social')->get(['id','razon_social','nombre_comercial'])
            : collect();

        $productos = $empresaId
            ? Producto::where('empresa_id', $empresaId)->orderBy('nombre')->get(['id','nombre','precio','stock'])
            : collect();

        $clientes = $empresaId
            ? Cliente::where('empresa_id', $empresaId)->orderBy('nombre')->get(['id','nombre','razon_social'])
            : collect();

        $responsables = $empresaId
            ? User::where('id_empresa', $empresaId)
                ->role(['vendedor','gerente','administrador_empresa','superadmin'])
                ->orderBy('nombre')
                ->get(['id','nombre','apellido_paterno','apellido_materno'])
            : collect();

        return view('ventas.create', compact('isSA','empresas','empresaId','productos','clientes','responsables'));
    }

    /* =========================================================================
     | STORE – Guardar venta/prefactura
     * ====================================================================== */
    public function store(Request $request)
    {
        $user   = $request->user();
        $isSA   = $user->hasRole('superadmin');
        $emId   = $this->resolveEmpresaId($request, $user);

        $data = $request->validate([
            'empresa_id'                  => [$isSA ? 'required' : 'nullable','integer','exists:empresas,id'],
            'cliente_id'                  => ['nullable','integer','exists:clientes,id'],
            'fecha_venta'                 => ['required','date'],
            'estatus'                     => ['required', Rule::in(['borrador','prefactura','facturada'])],
            'observaciones'               => ['nullable','string','max:2000'],
            'usuario_id'                  => ['nullable','integer','exists:users,id'],
            'items'                       => ['required','array','min:1'],
            'items.*.producto_id'         => ['required','integer','exists:productos,id'],
            'items.*.cantidad'            => ['required','numeric','min:0.01'],
            'items.*.precio_unitario'     => ['required','numeric','min:0'],
            'items.*.descuento'           => ['nullable','numeric','min:0'],
        ], [
            'empresa_id.required' => 'Selecciona la empresa.',
            'items.required'      => 'Agrega al menos un producto.',
        ]);

        $data['empresa_id'] = $emId;
        $data['usuario_id'] = $user->hasAnyRole(['superadmin','administrador_empresa','gerente'])
            ? (int) ($request->get('usuario_id', $user->id))
            : (int) $user->id;

        $calc = $this->validarYCalcularItems($data['items'], $emId, $data['estatus'] === 'facturada');
        if ($calc['error']) {
            return back()->withErrors($calc['error'])->withInput();
        }

        try {
            DB::beginTransaction();

            $venta = Venta::create([
                'empresa_id'    => $data['empresa_id'],
                'cliente_id'    => $data['cliente_id'] ?? null,
                'usuario_id'    => $data['usuario_id'],
                'fecha_venta'   => $data['fecha_venta'],
                'subtotal'      => $calc['subtotal'],
                'iva'           => $calc['iva'],
                'total'         => $calc['total'],
                'estatus'       => $data['estatus'],
                'observaciones' => $data['observaciones'] ?? null,
            ]);

            foreach ($calc['rows'] as $r) {
                $venta->detalle()->create($r);
            }

            if ($venta->estatus === 'facturada') {
                foreach ($calc['rows'] as $r) {
                    Producto::where('id', $r['producto_id'])->decrement('stock', $r['cantidad']);
                }
            }

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('Ventas.store error', ['e' => $e]);
            return back()->withErrors('No se pudo guardar la venta: '.$e->getMessage())->withInput();
        }

        return redirect()->route('ventas.show', $venta)->with('status', 'Venta guardada.');
    }

    /* =========================================================================
     | SHOW – Detalle
     * ====================================================================== */
    public function show(Request $request, Venta $venta)
    {
        try {
            $this->authorizeScopeVenta($venta, $request->user());

            $venta->load([
                'empresa:id,razon_social,nombre_comercial,rfc,sitio_web,logo_path',
                'usuario:id,nombre,apellido_paterno,apellido_materno',
                'cliente:id,nombre,razon_social',
                'detalle.producto:id,nombre'
            ]);

            return view('ventas.show', compact('venta'));
        } catch (Throwable $e) {
            Log::error('Ventas.show error', ['e' => $e, 'venta' => $venta->id ?? null]);
            return back()->withErrors('No se pudo cargar el detalle de la venta.');
        }
    }

    /* =========================================================================
     | EDIT – Formulario de edición
     * ====================================================================== */
    public function edit(Request $request, Venta $venta)
    {
        $user = $request->user();
        $this->authorizeScopeVenta($venta, $user);

        if ($venta->estatus === 'cancelada') {
            return back()->withErrors('La venta cancelada no puede editarse.');
        }

        $isSA      = $user->hasRole('superadmin');
        $empresaId = (int) $venta->empresa_id;

        $empresas = $isSA
            ? Empresa::orderBy('razon_social')->get(['id','razon_social','nombre_comercial'])
            : collect();

        $productos = Producto::where('empresa_id', $empresaId)->orderBy('nombre')->get(['id','nombre','precio','stock']);
        $clientes  = Cliente::where('empresa_id', $empresaId)->orderBy('nombre')->get(['id','nombre','razon_social']);
        $responsables = User::where('id_empresa', $empresaId)
            ->role(['vendedor','gerente','administrador_empresa','superadmin'])
            ->orderBy('nombre')->get(['id','nombre','apellido_paterno','apellido_materno']);

        $venta->load('detalle');

        return view('ventas.edit', compact('venta','isSA','empresas','productos','clientes','responsables'));
    }

    /* =========================================================================
     | UPDATE – Actualizar
     * ====================================================================== */
    public function update(Request $request, Venta $venta)
    {
        $user = $request->user();
        $this->authorizeScopeVenta($venta, $user);

        if ($venta->estatus === 'cancelada') {
            return back()->withErrors('La venta cancelada no puede editarse.');
        }

        $empresaId = (int) $venta->empresa_id;

        $data = $request->validate([
            'cliente_id'                  => ['nullable','integer','exists:clientes,id'],
            'fecha_venta'                 => ['required','date'],
            'estatus'                     => ['required', Rule::in(['borrador','prefactura','facturada','cancelada'])],
            'observaciones'               => ['nullable','string','max:2000'],
            'usuario_id'                  => ['nullable','integer','exists:users,id'],
            'items'                       => ['required','array','min:1'],
            'items.*.producto_id'         => ['required','integer','exists:productos,id'],
            'items.*.cantidad'            => ['required','numeric','min:0.01'],
            'items.*.precio_unitario'     => ['required','numeric','min:0'],
            'items.*.descuento'           => ['nullable','numeric','min:0'],
        ], [
            'items.required' => 'Agrega al menos un producto.',
        ]);

        $data['usuario_id'] = $user->hasAnyRole(['superadmin','administrador_empresa','gerente'])
            ? (int) ($request->get('usuario_id', $venta->usuario_id))
            : (int) $venta->usuario_id;

        $calc = $this->validarYCalcularItems($data['items'], $empresaId, $data['estatus'] === 'facturada');
        if ($calc['error']) {
            return back()->withErrors($calc['error'])->withInput();
        }

        try {
            DB::beginTransaction();

            $venta->refresh()->load('detalle:venta_id,producto_id,cantidad');

            // Si estaba facturada, restaurar stock previo
            if ($venta->estatus === 'facturada') {
                foreach ($venta->detalle as $d) {
                    Producto::where('id', $d->producto_id)->increment('stock', $d->cantidad);
                }
            }

            // Actualizar encabezado
            $venta->update([
                'cliente_id'    => $data['cliente_id'] ?? null,
                'usuario_id'    => $data['usuario_id'],
                'fecha_venta'   => $data['fecha_venta'],
                'subtotal'      => $calc['subtotal'],
                'iva'           => $calc['iva'],
                'total'         => $calc['total'],
                'estatus'       => $data['estatus'],
                'observaciones' => $data['observaciones'] ?? null,
            ]);

            // Reemplazar detalle
            DetalleVenta::where('venta_id', $venta->id)->delete();
            foreach ($calc['rows'] as $r) {
                $venta->detalle()->create($r);
            }

            // Si quedó facturada, descontar stock nuevo
            if ($venta->estatus === 'facturada') {
                foreach ($calc['rows'] as $r) {
                    Producto::where('id', $r['producto_id'])->decrement('stock', $r['cantidad']);
                }
            }

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('Ventas.update error', ['e' => $e, 'venta' => $venta->id]);
            return back()->withErrors('No se pudo actualizar la venta: '.$e->getMessage())->withInput();
        }

        return redirect()->route('ventas.show', $venta)->with('status', 'Venta actualizada.');
    }

    /* =========================================================================
     | DESTROY – Eliminar (restaura stock si estaba facturada)
     * ====================================================================== */
    public function destroy(Request $request, Venta $venta)
    {
        $user = $request->user();
        $this->authorizeScopeVenta($venta, $user);

        try {
            DB::beginTransaction();

            if ($venta->estatus === 'facturada') {
                $venta->load('detalle:venta_id,producto_id,cantidad');
                foreach ($venta->detalle as $d) {
                    Producto::where('id', $d->producto_id)->increment('stock', $d->cantidad);
                }
            }

            DetalleVenta::where('venta_id', $venta->id)->delete();
            $venta->delete();

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('Ventas.destroy error', ['e' => $e, 'venta' => $venta->id]);
            return back()->withErrors('No se pudo eliminar la venta: '.$e->getMessage());
        }

        return redirect()->route('ventas.index', ['empresa_id' => $this->safeEmpresaForRedirect($user, $venta->empresa_id)])
            ->with('status', 'Venta eliminada.');
    }

    /* =========================================================================
     | convertirPrefactura – Prefactura/Borrador → Facturada
     * ====================================================================== */
    public function convertirPrefactura(Request $request, Venta $venta)
    {
        $user = $request->user();
        $this->authorizeScopeVenta($venta, $user);

        if (!in_array($venta->estatus, ['borrador','prefactura'])) {
            return back()->withErrors('Solo se pueden convertir borradores/prefacturas.');
        }

        try {
            DB::beginTransaction();

            $venta->load(['detalle','detalle.producto']);

            foreach ($venta->detalle as $d) {
                $prod = $d->producto;
                if (!$prod || (int)$prod->empresa_id !== (int)$venta->empresa_id) {
                    DB::rollBack();
                    return back()->withErrors("Una línea tiene producto de otra empresa.");
                }
                if ($prod->stock < $d->cantidad) {
                    DB::rollBack();
                    return back()->withErrors("Stock insuficiente para {$prod->nombre}. Disponible: {$prod->stock}");
                }
            }

            foreach ($venta->detalle as $d) {
                Producto::where('id', $d->producto_id)->decrement('stock', $d->cantidad);
            }

            $venta->update(['estatus' => 'facturada']);
            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('Ventas.convertirPrefactura error', ['e' => $e, 'venta' => $venta->id]);
            return back()->withErrors('No se pudo convertir la prefactura: '.$e->getMessage());
        }

        return redirect()->route('ventas.show', $venta)->with('status', 'Prefactura convertida a venta.');
    }

    /* =========================================================================
     | PDF – Generar PDF con logo de empresa (si existe)
     * ====================================================================== */
    public function pdf(Request $request, Venta $venta)
    {
        try {
            $this->authorizeScopeVenta($venta, $request->user());

            $venta->load([
                'empresa',
                'usuario',
                'cliente',
                'detalle.producto',
            ]);

            $logoDataUri = $this->logoDataUri($venta->empresa);

            $pdf = Pdf::loadView('ventas.pdf', [
                'venta'       => $venta,
                'logoDataUri' => $logoDataUri,
            ])->setPaper('letter', 'portrait');

            return $pdf->stream('venta-'.$venta->id.'.pdf');
        } catch (Throwable $e) {
            Log::error('Ventas.pdf error', ['venta' => $venta->id ?? null, 'e' => $e]);
            return back()->withErrors('No se pudo generar el PDF: '.$e->getMessage());
        }
    }

    /* =========================================================================
     | Helpers privados (empresa, permisos, cálculo, logo)
     * ====================================================================== */

    private function resolveEmpresaId(Request $request, User $user): int
    {
        if ($user->hasRole('superadmin')) {
            $emId = (int) $request->get('empresa_id', 0);
            abort_unless($emId > 0 && Empresa::whereKey($emId)->exists(), 422, 'Empresa inválida.');
            return $emId;
        }
        $emId = (int) $user->id_empresa;
        abort_unless($emId > 0, 403, 'Tu usuario no tiene empresa asignada.');
        return $emId;
    }

    private function resolveEmpresaIdForList(Request $request, User $user): int
    {
        if ($user->hasRole('superadmin')) {
            $emId = (int) $request->get('empresa_id', 0);
            if ($emId > 0 && Empresa::whereKey($emId)->exists()) return $emId;
            $first = Empresa::orderBy('id')->value('id');
            return (int) ($first ?? 0);
        }
        $emId = (int) $user->id_empresa;
        abort_unless($emId > 0, 403, 'Tu usuario no tiene empresa asignada.');
        return $emId;
    }

    private function authorizeScopeVenta(Venta $venta, User $user): void
    {
        if ($user->hasRole('superadmin')) return;
        if ((int)$venta->empresa_id !== (int)$user->id_empresa) {
            abort(403, 'No puedes acceder a ventas de otra empresa.');
        }
        if ($user->hasRole('vendedor') && (int)$venta->usuario_id !== (int)$user->id) {
            abort(403, 'No puedes acceder a ventas de otros usuarios.');
        }
    }

    private function validarYCalcularItems(array $items, int $empresaId, bool $needStock = false): array
    {
        $subtotal = 0.0;
        $rows = [];

        foreach ($items as $row) {
            $pid  = (int) ($row['producto_id'] ?? 0);
            $cant = (float) ($row['cantidad'] ?? 0);
            $pu   = (float) ($row['precio_unitario'] ?? 0);
            $desc = (float) ($row['descuento'] ?? 0);

            $prod = Producto::where('id', $pid)->where('empresa_id', $empresaId)->first();
            if (!$prod) {
                return ['error' => "El producto seleccionado no pertenece a la empresa.", 'rows' => [], 'subtotal' => 0, 'iva' => 0, 'total' => 0];
            }
            if ($needStock && $prod->stock < $cant) {
                return ['error' => "Stock insuficiente para {$prod->nombre}. Disponible: {$prod->stock}", 'rows' => [], 'subtotal' => 0, 'iva' => 0, 'total' => 0];
            }
            if ($pu <= 0) $pu = (float) $prod->precio;

            $linea = max(($cant * $pu) - $desc, 0);
            $subtotal += $linea;

            $rows[] = [
                'producto_id'     => $pid,
                'cantidad'        => $cant,
                'precio_unitario' => $pu,
                'descuento'       => $desc,
                'total_linea'     => $linea,
            ];
        }

        $iva   = round($subtotal * 0.16, 2);
        $total = round($subtotal + $iva, 2);

        return ['error' => null, 'rows' => $rows, 'subtotal' => $subtotal, 'iva' => $iva, 'total' => $total];
        }

    private function safeEmpresaForRedirect(User $user, int $empresaId): int
    {
        return $user->hasRole('superadmin') ? $empresaId : (int) $user->id_empresa;
    }

    /**
     * Logo de empresa como Data URI (si existe).
     * Acepta rutas absolutas y relativas a Storage (public/local).
     */
    private function logoDataUri(?Empresa $empresa): ?string
    {
        try {
            if (!$empresa || empty($empresa->logo_path)) return null;
            $path = $empresa->logo_path;

            // Ruta absoluta
            if (is_string($path) && file_exists($path)) {
                $mime = mime_content_type($path) ?: 'image/png';
                $data = file_get_contents($path);
                if ($data === false) return null;
                return 'data:'.$mime.';base64,'.base64_encode($data);
            }

            // Storage public
            if (Storage::disk('public')->exists($path)) {
                $mime = Storage::disk('public')->mimeType($path) ?: 'image/png';
                $data = Storage::disk('public')->get($path);
                return 'data:'.$mime.';base64,'.base64_encode($data);
            }

            // Storage local
            if (Storage::disk('local')->exists($path)) {
                $mime = Storage::disk('local')->mimeType($path) ?: 'image/png';
                $data = Storage::disk('local')->get($path);
                return 'data:'.$mime.';base64,'.base64_encode($data);
            }

            // public/storage/...
            if (str_starts_with($path, 'storage/')) {
                $abs = public_path($path);
                if (file_exists($abs)) {
                    $mime = mime_content_type($abs) ?: 'image/png';
                    $data = file_get_contents($abs);
                    if ($data !== false) {
                        return 'data:'.$mime.';base64,'.base64_encode($data);
                    }
                }
            }
        } catch (Throwable $e) {
            Log::warning('Logo Data URI error', ['empresa' => $empresa->id ?? null, 'e' => $e->getMessage()]);
        }
        return null;
    }
}
