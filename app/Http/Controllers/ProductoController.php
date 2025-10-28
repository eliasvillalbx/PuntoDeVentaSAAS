<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\Categoria;
use App\Models\Empresa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProductoController extends Controller
{
    public function __construct()
    {
       // $this->middleware(['auth','verified']);
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $isSA = $user->hasRole('superadmin');

        $q          = trim((string)$request->get('q',''));
        $empresaId  = $isSA ? $request->integer('empresa_id') : $user->id_empresa;
        $categoriaId= $request->integer('categoria_id') ?: null;
        $activo     = $request->filled('activo') ? (int)$request->boolean('activo') : null;

        $productos = Producto::query()
            ->with(['categoria'])
            ->when(!$isSA, fn($qry) => $qry->deEmpresa($empresaId))
            ->when($isSA && $empresaId, fn($qry) => $qry->deEmpresa($empresaId))
            ->when($categoriaId, fn($qry) => $qry->where('categoria_id', $categoriaId))
            ->when(!is_null($activo), fn($qry) => $qry->where('activo', $activo))
            ->when($q !== '', function ($qry) use ($q) {
                $qry->where(function ($s) use ($q) {
                    $s->where('nombre','like',"%{$q}%")
                      ->orWhere('sku','like',"%{$q}%")
                      ->orWhere('descripcion','like',"%{$q}%");
                });
            })
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $empresas   = $isSA ? Empresa::orderBy('razon_social')->get(['id','razon_social']) : collect();
        $categorias = Categoria::query()
            ->when(!$isSA, fn($q2) => $q2->deEmpresa($user->id_empresa))
            ->when($isSA && $empresaId, fn($q2) => $q2->deEmpresa($empresaId))
            ->orderBy('nombre')->get(['id','nombre','id_empresa']);

        return view('productos.index', compact('productos','q','empresas','empresaId','categorias','categoriaId','activo'));
    }

    public function create()
    {
        $user = Auth::user();
        $isSA = $user->hasRole('superadmin');

        $empresas = $isSA ? Empresa::orderBy('razon_social')->get(['id','razon_social']) : collect();
        $categorias = Categoria::query()
            ->when(!$isSA, fn($q) => $q->deEmpresa($user->id_empresa))
            ->orderBy('nombre')->get(['id','nombre','id_empresa']);

        return view('productos.create', compact('empresas','categorias'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user->hasAnyRole(['superadmin','administrador_empresa','gerente'])) {
            return back()->withInput()->with('error','No tienes permisos para crear productos.');
        }

        $isSA = $user->hasRole('superadmin');

        $data = $request->validate([
            'id_empresa'   => [$isSA ? 'required' : 'nullable','integer','exists:empresas,id'],
            'categoria_id' => ['nullable','integer','exists:categorias,id'],
            'nombre'       => ['required','string','max:180'],
            'sku'          => ['nullable','string','max:100'],
            'precio'       => ['required','numeric','min:0','max:9999999999.99'],
            'costo_referencial' => ['nullable','numeric','min:0','max:9999999999.99'],
            'moneda_venta' => ['nullable','string','size:3'],
            'stock'        => ['required','integer','min:0','max:100000000'],
            'descripcion'  => ['nullable','string','max:5000'],
            'activo'       => ['nullable','boolean'],
            'imagen'       => ['nullable','image','max:2048'],
        ]);

        if (!$isSA) $data['id_empresa'] = $user->id_empresa;

        DB::beginTransaction();
        try {
            $imagenPath = null;
            if ($request->hasFile('imagen')) {
                $imagenPath = $request->file('imagen')->store('productos','public');
            }

            Producto::create([
                'id_empresa'   => $data['id_empresa'],
                'categoria_id' => $data['categoria_id'] ?? null,
                'nombre'       => $data['nombre'],
                'sku'          => $data['sku'] ?? null,
                'precio'       => $data['precio'],
                'costo_referencial' => $data['costo_referencial'] ?? null,
                'moneda_venta' => $data['moneda_venta'] ?? 'MXN',
                'stock'        => $data['stock'],
                'descripcion'  => $data['descripcion'] ?? null,
                'imagen_path'  => $imagenPath,
                'activo'       => isset($data['activo']) ? (bool)$data['activo'] : true,
            ]);

            DB::commit();
            return redirect()->route('productos.index')->with('success','Producto creado correctamente.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Producto store error', ['e'=>$e->getMessage()]);
            return back()->withInput()->with('error','Ocurrió un error al crear el producto.');
        }
    }

    public function show(Producto $producto)
    {
        $user = Auth::user();
        $isSA = $user->hasRole('superadmin');
        if (!$isSA && $producto->id_empresa !== $user->id_empresa) {
            return redirect()->route('productos.index')->with('error','No puedes ver productos de otra empresa.');
        }

        $producto->load(['categoria','proveedores']);
        return view('productos.show', compact('producto'));
    }

    public function edit(Producto $producto)
    {
        $user = Auth::user();
        $isSA = $user->hasRole('superadmin');
        if (!$isSA && $producto->id_empresa !== $user->id_empresa) {
            return redirect()->route('productos.index')->with('error','No puedes editar productos de otra empresa.');
        }

        $empresas = $isSA ? Empresa::orderBy('razon_social')->get(['id','razon_social']) : collect();
        $categorias = Categoria::query()
            ->when(!$isSA, fn($q) => $q->deEmpresa($user->id_empresa))
            ->when($isSA, fn($q) => $q->deEmpresa($producto->id_empresa))
            ->orderBy('nombre')->get(['id','nombre','id_empresa']);

        return view('productos.edit', compact('producto','empresas','categorias'));
    }

    public function update(Request $request, Producto $producto)
    {
        $user = Auth::user();
        $isSA = $user->hasRole('superadmin');
        if (!$isSA && $producto->id_empresa !== $user->id_empresa) {
            return redirect()->route('productos.index')->with('error','No puedes actualizar productos de otra empresa.');
        }

        $data = $request->validate([
            'id_empresa'   => [$isSA ? 'required' : 'nullable','integer','exists:empresas,id'],
            'categoria_id' => ['nullable','integer','exists:categorias,id'],
            'nombre'       => ['required','string','max:180'],
            'sku'          => ['nullable','string','max:100'],
            'precio'       => ['required','numeric','min:0','max:9999999999.99'],
            'costo_referencial' => ['nullable','numeric','min:0','max:9999999999.99'],
            'moneda_venta' => ['nullable','string','size:3'],
            'stock'        => ['required','integer','min:0','max:100000000'],
            'descripcion'  => ['nullable','string','max:5000'],
            'activo'       => ['nullable','boolean'],
            'imagen'       => ['nullable','image','max:2048'],
        ]);

        if (!$isSA) $data['id_empresa'] = $user->id_empresa;

        DB::beginTransaction();
        try {
            if ($request->hasFile('imagen')) {
                if ($producto->imagen_path) {
                    Storage::disk('public')->delete($producto->imagen_path);
                }
                $producto->imagen_path = $request->file('imagen')->store('productos','public');
            }

            $producto->fill([
                'id_empresa'   => $data['id_empresa'],
                'categoria_id' => $data['categoria_id'] ?? null,
                'nombre'       => $data['nombre'],
                'sku'          => $data['sku'] ?? null,
                'precio'       => $data['precio'],
                'costo_referencial' => $data['costo_referencial'] ?? $producto->costo_referencial,
                'moneda_venta' => $data['moneda_venta'] ?? $producto->moneda_venta,
                'stock'        => $data['stock'],
                'descripcion'  => $data['descripcion'] ?? null,
                'activo'       => isset($data['activo']) ? (bool)$data['activo'] : $producto->activo,
            ])->save();

            DB::commit();
            return redirect()->route('productos.index')->with('success','Producto actualizado correctamente.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Producto update error', ['e'=>$e->getMessage(),'id'=>$producto->id]);
            return back()->withInput()->with('error','Ocurrió un error al actualizar el producto.');
        }
    }

    public function destroy(Producto $producto)
    {
        $user = Auth::user();
        $isSA = $user->hasRole('superadmin');
        if (!$isSA && $producto->id_empresa !== $user->id_empresa) {
            return back()->with('error','No puedes eliminar productos de otra empresa.');
        }

        DB::beginTransaction();
        try {
            if ($producto->imagen_path) {
                Storage::disk('public')->delete($producto->imagen_path);
            }
            $producto->delete();
            DB::commit();
            return redirect()->route('productos.index')->with('success','Producto eliminado.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Producto destroy error', ['e'=>$e->getMessage(),'id'=>$producto->id]);
            return back()->with('error','No se pudo eliminar el producto.');
        }
    }
}
