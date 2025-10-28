<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Empresa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CategoriaController extends Controller
{
    public function __construct()
    {
    }

    public function index(Request $request)
    {
        $user  = Auth::user();
        $isSA  = $user->hasRole('superadmin');

        $q         = trim((string) $request->get('q',''));
        $empresaId = $isSA ? $request->integer('empresa_id') : $user->id_empresa;
        $activa    = $request->filled('activa') ? (int)$request->boolean('activa') : null;

        $cats = Categoria::query()
            ->when(!$isSA, fn($qry) => $qry->deEmpresa($empresaId))
            ->when($isSA && $empresaId, fn($qry) => $qry->deEmpresa($empresaId))
            ->when($q !== '', function ($qry) use ($q) {
                $qry->where(function ($s) use ($q) {
                    $s->where('nombre','like',"%{$q}%")
                      ->orWhere('descripcion','like',"%{$q}%");
                });
            })
            ->when(!is_null($activa), fn($qry) => $qry->where('activa', $activa))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $empresas = $isSA ? Empresa::orderBy('razon_social')->get(['id','razon_social']) : collect();

        return view('categorias.index', compact('cats','q','activa','empresas','empresaId'));
    }

    public function create()
    {
        $user = Auth::user();
        $empresas = $user->hasRole('superadmin')
            ? Empresa::orderBy('razon_social')->get(['id','razon_social'])
            : collect();

        return view('categorias.create', compact('empresas'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user->hasAnyRole(['superadmin','administrador_empresa','gerente'])) {
            return back()->withInput()->with('error','No tienes permisos para crear categorías.');
        }

        $isSA = $user->hasRole('superadmin');

        $data = $request->validate([
            'id_empresa'  => [$isSA ? 'required' : 'nullable','integer','exists:empresas,id'],
            'nombre'      => ['required','string','max:120'],
            'descripcion' => ['nullable','string','max:1000'],
            'activa'      => ['nullable','boolean'],
        ]);

        if (!$isSA) $data['id_empresa'] = $user->id_empresa;

        DB::beginTransaction();
        try {
            Categoria::create([
                'id_empresa'  => $data['id_empresa'],
                'nombre'      => $data['nombre'],
                'descripcion' => $data['descripcion'] ?? null,
                'activa'      => isset($data['activa']) ? (bool)$data['activa'] : true,
            ]);

            DB::commit();
            return redirect()->route('categorias.index')->with('success','Categoría creada correctamente.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Categoria store error', ['e'=>$e->getMessage()]);
            return back()->withInput()->with('error','Ocurrió un error al crear la categoría.');
        }
    }

    public function show(Categoria $categoria)
    {
        $user = Auth::user();
        $isSA = $user->hasRole('superadmin');
        if (!$isSA && $categoria->id_empresa !== $user->id_empresa) {
            return redirect()->route('categorias.index')->with('error','No puedes ver categorías de otra empresa.');
        }
        return view('categorias.show', compact('categoria'));
    }

    public function edit(Categoria $categoria)
    {
        $user = Auth::user();
        $isSA = $user->hasRole('superadmin');
        if (!$isSA && $categoria->id_empresa !== $user->id_empresa) {
            return redirect()->route('categorias.index')->with('error','No puedes editar categorías de otra empresa.');
        }

        $empresas = $isSA ? Empresa::orderBy('razon_social')->get(['id','razon_social']) : collect();
        return view('categorias.edit', compact('categoria','empresas'));
    }

    public function update(Request $request, Categoria $categoria)
    {
        $user = Auth::user();
        $isSA = $user->hasRole('superadmin');
        if (!$isSA && $categoria->id_empresa !== $user->id_empresa) {
            return redirect()->route('categorias.index')->with('error','No puedes actualizar categorías de otra empresa.');
        }

        $data = $request->validate([
            'id_empresa'  => [$isSA ? 'required' : 'nullable','integer','exists:empresas,id'],
            'nombre'      => ['required','string','max:120'],
            'descripcion' => ['nullable','string','max:1000'],
            'activa'      => ['nullable','boolean'],
        ]);

        if (!$isSA) $data['id_empresa'] = $user->id_empresa;

        DB::beginTransaction();
        try {
            $categoria->fill([
                'id_empresa'  => $data['id_empresa'],
                'nombre'      => $data['nombre'],
                'descripcion' => $data['descripcion'] ?? null,
                'activa'      => isset($data['activa']) ? (bool)$data['activa'] : $categoria->activa,
            ])->save();

            DB::commit();
            return redirect()->route('categorias.index')->with('success','Categoría actualizada correctamente.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Categoria update error', ['e'=>$e->getMessage(),'id'=>$categoria->id]);
            return back()->withInput()->with('error','Ocurrió un error al actualizar la categoría.');
        }
    }

    public function destroy(Categoria $categoria)
    {
        $user = Auth::user();
        $isSA = $user->hasRole('superadmin');
        if (!$isSA && $categoria->id_empresa !== $user->id_empresa) {
            return back()->with('error','No puedes eliminar categorías de otra empresa.');
        }

        DB::beginTransaction();
        try {
            $categoria->delete();
            DB::commit();
            return redirect()->route('categorias.index')->with('success','Categoría eliminada.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Categoria destroy error', ['e'=>$e->getMessage(),'id'=>$categoria->id]);
            return back()->with('error','No se pudo eliminar la categoría.');
        }
    }
}
