<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\Proveedor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductoProveedorController extends Controller
{
    public function __construct()
    {
    }

    /** Crear o actualizar costo de un proveedor para un producto */
    public function store(Request $request, Producto $producto)
    {
        $user = Auth::user();
        $isSA = $user->hasRole('superadmin');
        if (!$isSA && $producto->id_empresa !== $user->id_empresa) {
            return back()->with('error','No puedes modificar un producto de otra empresa.');
        }

        $data = $request->validate([
            'proveedor_id'   => ['required','integer','exists:proveedores,id'],
            'sku_proveedor'  => ['nullable','string','max:120'],
            'costo'          => ['required','numeric','min:0','max:9999999999.99'],
            'moneda'         => ['required','string','size:3'],
            'lead_time_dias' => ['nullable','integer','min:0','max:3650'],
            'moq'            => ['nullable','integer','min:1','max:1000000'],
            'preferido'      => ['nullable','boolean'],
            'activo'         => ['nullable','boolean'],
        ]);

        $proveedor = Proveedor::findOrFail($data['proveedor_id']);

        if (!$isSA && $proveedor->id_empresa !== $user->id_empresa) {
            return back()->with('error','El proveedor no pertenece a tu empresa.');
        }
        if ($isSA && $proveedor->id_empresa !== $producto->id_empresa) {
            return back()->with('error','Proveedor y Producto deben ser de la misma empresa.');
        }

        DB::beginTransaction();
        try {
            $payload = [
                'sku_proveedor'  => $data['sku_proveedor'] ?? null,
                'costo'          => $data['costo'],
                'moneda'         => $data['moneda'] ?? 'MXN',
                'lead_time_dias' => $data['lead_time_dias'] ?? 0,
                'moq'            => $data['moq'] ?? 1,
                'preferido'      => (bool)($data['preferido'] ?? false),
                'activo'         => (bool)($data['activo'] ?? true),
            ];

            if (!empty($payload['preferido'])) {
                // Desmarcar otros preferidos del mismo producto
                $producto->proveedores()->updateExistingPivot(
                    $producto->proveedores()->pluck('proveedor_id')->toArray(),
                    ['preferido' => false]
                );
            }

            $producto->proveedores()->syncWithoutDetaching([
                $proveedor->id => $payload
            ]);

            DB::commit();
            return back()->with('success','Proveedor vinculado/actualizado para el producto.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Pivot store error', ['e'=>$e->getMessage(),'producto'=>$producto->id]);
            return back()->with('error','No se pudo guardar el costo del proveedor.');
        }
    }

    /** Actualizar una fila pivote específica */
    public function update(Request $request, Producto $producto, Proveedor $proveedor)
    {
        $user = Auth::user();
        $isSA = $user->hasRole('superadmin');

        if (
            (!$isSA && ($producto->id_empresa !== $user->id_empresa || $proveedor->id_empresa !== $user->id_empresa)) ||
            ($isSA && $proveedor->id_empresa !== $producto->id_empresa)
        ) {
            return back()->with('error','No puedes modificar registros de otra empresa.');
        }

        $data = $request->validate([
            'sku_proveedor'  => ['nullable','string','max:120'],
            'costo'          => ['required','numeric','min:0','max:9999999999.99'],
            'moneda'         => ['required','string','size:3'],
            'lead_time_dias' => ['nullable','integer','min:0','max:3650'],
            'moq'            => ['nullable','integer','min:1','max:1000000'],
            'preferido'      => ['nullable','boolean'],
            'activo'         => ['nullable','boolean'],
        ]);

        DB::beginTransaction();
        try {
            $payload = [
                'sku_proveedor'  => $data['sku_proveedor'] ?? null,
                'costo'          => $data['costo'],
                'moneda'         => $data['moneda'] ?? 'MXN',
                'lead_time_dias' => $data['lead_time_dias'] ?? 0,
                'moq'            => $data['moq'] ?? 1,
                'preferido'      => (bool)($data['preferido'] ?? false),
                'activo'         => (bool)($data['activo'] ?? true),
            ];

            if (!empty($payload['preferido'])) {
                $producto->proveedores()->updateExistingPivot(
                    $producto->proveedores()->pluck('proveedor_id')->toArray(),
                    ['preferido' => false]
                );
            }

            $producto->proveedores()->updateExistingPivot($proveedor->id, $payload);

            DB::commit();
            return back()->with('success','Costo de proveedor actualizado.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Pivot update error', ['e'=>$e->getMessage(),'p'=>$producto->id,'prov'=>$proveedor->id]);
            return back()->with('error','No se pudo actualizar el registro.');
        }
    }

    /** Quitar proveedor del producto */
    public function destroy(Producto $producto, Proveedor $proveedor)
    {
        $user = Auth::user();
        $isSA = $user->hasRole('superadmin');

        if (
            (!$isSA && ($producto->id_empresa !== $user->id_empresa || $proveedor->id_empresa !== $user->id_empresa))
        ) {
            return back()->with('error','No puedes modificar registros de otra empresa.');
        }

        DB::beginTransaction();
        try {
            $producto->proveedores()->detach($proveedor->id);
            DB::commit();
            return back()->with('success','Proveedor desvinculado del producto.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Pivot destroy error', ['e'=>$e->getMessage(),'p'=>$producto->id,'prov'=>$proveedor->id]);
            return back()->with('error','No se pudo desvincular.');
        }
    }
}
