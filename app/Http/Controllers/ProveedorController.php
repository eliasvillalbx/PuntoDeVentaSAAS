<?php

namespace App\Http\Controllers;

use App\Models\Proveedor;
use App\Models\Empresa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProveedorController extends Controller
{
    public function __construct()
    {
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $isSA = $user->hasRole('superadmin');

        $q         = trim((string)$request->get('q',''));
        $empresaId = $isSA ? $request->integer('empresa_id') : $user->id_empresa;
        $activo    = $request->filled('activo') ? (int)$request->boolean('activo') : null;

        $proveedores = Proveedor::query()
            ->when(!$isSA, fn($qry) => $qry->deEmpresa($empresaId))
            ->when($isSA && $empresaId, fn($qry) => $qry->deEmpresa($empresaId))
            ->when(!is_null($activo), fn($qry) => $qry->where('activo', $activo))
            ->when($q !== '', function ($qry) use ($q) {
                $qry->where(function ($s) use ($q) {
                    $s->where('nombre','like',"%{$q}%")
                      ->orWhere('rfc','like',"%{$q}%")
                      ->orWhere('email','like',"%{$q}%")
                      ->orWhere('telefono','like',"%{$q}%")
                      ->orWhere('contacto','like',"%{$q}%");
                });
            })
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $empresas = $isSA ? Empresa::orderBy('razon_social')->get(['id','razon_social']) : collect();

        return view('proveedores.index', compact('proveedores','q','empresas','empresaId','activo'));
    }

    public function create()
    {
        $user = Auth::user();
        $empresas = $user->hasRole('superadmin')
            ? Empresa::orderBy('razon_social')->get(['id','razon_social'])
            : collect();

        return view('proveedores.create', compact('empresas'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user->hasAnyRole(['superadmin','administrador_empresa','gerente'])) {
            return back()->withInput()->with('error','No tienes permisos para crear proveedores.');
        }

        $isSA = $user->hasRole('superadmin');

        $data = $request->validate([
            'id_empresa' => [$isSA ? 'required' : 'nullable','integer','exists:empresas,id'],
            'nombre'     => ['required','string','max:180'],
            'rfc'        => ['nullable','string','max:13'],
            'email'      => ['nullable','email','max:180'],
            'telefono'   => ['nullable','string','max:50'],
            'contacto'   => ['nullable','string','max:120'],
            'activo'     => ['nullable','boolean'],
        ]);

        if (!$isSA) $data['id_empresa'] = $user->id_empresa;

        DB::beginTransaction();
        try {
            Proveedor::create([
                'id_empresa' => $data['id_empresa'],
                'nombre'     => $data['nombre'],
                'rfc'        => $data['rfc'] ?? null,
                'email'      => $data['email'] ?? null,
                'telefono'   => $data['telefono'] ?? null,
                'contacto'   => $data['contacto'] ?? null,
                'activo'     => isset($data['activo']) ? (bool)$data['activo'] : true,
            ]);

            DB::commit();
            return redirect()->route('proveedores.index')->with('success','Proveedor creado correctamente.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Proveedor store error', ['e'=>$e->getMessage()]);
            return back()->withInput()->with('error','Ocurrió un error al crear el proveedor.');
        }
    }

    public function show(Proveedor $proveedore) // {proveedore} por convención resource
    {
        $user = Auth::user();
        $isSA = $user->hasRole('superadmin');
        if (!$isSA && $proveedore->id_empresa !== $user->id_empresa) {
            return redirect()->route('proveedores.index')->with('error','No puedes ver proveedores de otra empresa.');
        }
        return view('proveedores.show', ['proveedor' => $proveedore]);
    }

    public function edit(Proveedor $proveedore)
    {
        $user = Auth::user();
        $isSA = $user->hasRole('superadmin');
        if (!$isSA && $proveedore->id_empresa !== $user->id_empresa) {
            return redirect()->route('proveedores.index')->with('error','No puedes editar proveedores de otra empresa.');
        }

        $empresas = $isSA ? Empresa::orderBy('razon_social')->get(['id','razon_social']) : collect();
        return view('proveedores.edit', ['proveedor' => $proveedore, 'empresas' => $empresas]);
    }

    public function update(Request $request, Proveedor $proveedore)
    {
        $user = Auth::user();
        $isSA = $user->hasRole('superadmin');
        if (!$isSA && $proveedore->id_empresa !== $user->id_empresa) {
            return redirect()->route('proveedores.index')->with('error','No puedes actualizar proveedores de otra empresa.');
        }

        $data = $request->validate([
            'id_empresa' => [$isSA ? 'required' : 'nullable','integer','exists:empresas,id'],
            'nombre'     => ['required','string','max:180'],
            'rfc'        => ['nullable','string','max:13'],
            'email'      => ['nullable','email','max:180'],
            'telefono'   => ['nullable','string','max:50'],
            'contacto'   => ['nullable','string','max:120'],
            'activo'     => ['nullable','boolean'],
        ]);

        if (!$isSA) $data['id_empresa'] = $user->id_empresa;

        DB::beginTransaction();
        try {
            $proveedore->fill([
                'id_empresa' => $data['id_empresa'],
                'nombre'     => $data['nombre'],
                'rfc'        => $data['rfc'] ?? null,
                'email'      => $data['email'] ?? null,
                'telefono'   => $data['telefono'] ?? null,
                'contacto'   => $data['contacto'] ?? null,
                'activo'     => isset($data['activo']) ? (bool)$data['activo'] : $proveedore->activo,
            ])->save();

            DB::commit();
            return redirect()->route('proveedores.index')->with('success','Proveedor actualizado correctamente.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Proveedor update error', ['e'=>$e->getMessage(),'id'=>$proveedore->id]);
            return back()->withInput()->with('error','Ocurrió un error al actualizar el proveedor.');
        }
    }

    public function destroy(Proveedor $proveedore)
    {
        $user = Auth::user();
        $isSA = $user->hasRole('superadmin');
        if (!$isSA && $proveedore->id_empresa !== $user->id_empresa) {
            return back()->with('error','No puedes eliminar proveedores de otra empresa.');
        }

        DB::beginTransaction();
        try {
            $proveedore->delete();
            DB::commit();
            return redirect()->route('proveedores.index')->with('success','Proveedor eliminado.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Proveedor destroy error', ['e'=>$e->getMessage(),'id'=>$proveedore->id]);
            return back()->with('error','No se pudo eliminar el proveedor.');
        }
    }
}
