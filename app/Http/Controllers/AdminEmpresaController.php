<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAdminEmpresaRequest;
use App\Http\Requests\UpdateAdminEmpresaRequest;
use App\Models\Empresa;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdminEmpresaController extends Controller
{
    public function __construct()
    {
        // Solo superadmin gestiona administradores de empresas (ajusta a tu estrategia)
    }

    public function index(Request $request)
    {
        $empresaId = (int) $request->integer('empresa_id');
        $q         = trim((string) $request->get('q', ''));

        $empresas  = Empresa::orderBy('razon_social')->get();

        $admins = User::query()
            ->with(['roles', 'empresa'])
            ->when($empresaId > 0, fn($q2) => $q2->where('id_empresa', $empresaId))
            ->when($q !== '', function ($qry) use ($q) {
                $qry->where(function ($w) use ($q) {
                    $w->where('nombre', 'like', "%{$q}%")
                      ->orWhere('apellido_paterno', 'like', "%{$q}%")
                      ->orWhere('apellido_materno', 'like', "%{$q}%")
                      ->orWhere('email', 'like', "%{$q}%");
                });
            })
            ->whereHas('roles', fn($r) => $r->where('name', 'administrador_empresa'))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('admin-empresas.index', compact('admins','empresas','empresaId','q'));
    }

    public function create()
    {
        $empresas = Empresa::orderBy('razon_social')->get();
        return view('admin-empresas.create', compact('empresas'));
    }

    public function store(StoreAdminEmpresaRequest $request): RedirectResponse
    {
        $data = $request->validated();

        try {
            return DB::transaction(function () use ($data) {
                $user = new User();
                $user->nombre            = $data['nombre'];
                $user->apellido_paterno  = $data['apellido_paterno'];
                $user->apellido_materno  = $data['apellido_materno'] ?? null;
                $user->telefono          = $data['telefono'] ?? null;
                $user->email             = $data['email'];
                // Cast 'password' => 'hashed' en tu modelo se encarga de hashear:
                $user->password          = $data['password'];
                $user->id_empresa        = (int) $data['id_empresa'];
                $user->save();

                // Rol debe existir en la BD
                $user->syncRoles(['administrador_empresa']);

                return redirect()
                    ->route('admin-empresas.index')
                    ->with('success', 'Administrador creado correctamente.');
            });
        } catch (\Throwable $e) {
            Log::error('Error al crear admin_empresa', ['e' => $e]);
            $msg = app()->environment('local') ? $e->getMessage() : 'No se pudo crear el administrador.';
            return back()->withInput()->withErrors($msg);
        }
    }

    public function edit(User $admin_empresa)
    {
        $empresas = Empresa::orderBy('razon_social')->get();

        return view('admin-empresas.edit', [
            'admin'    => $admin_empresa,
            'empresas' => $empresas,
        ]);
    }

    public function update(UpdateAdminEmpresaRequest $request, User $admin_empresa): RedirectResponse
    {
        $data = $request->validated();

        try {
            return DB::transaction(function () use ($data, $admin_empresa) {
                $admin_empresa->nombre           = $data['nombre'];
                $admin_empresa->apellido_paterno = $data['apellido_paterno'];
                $admin_empresa->apellido_materno = $data['apellido_materno'] ?? null;
                $admin_empresa->telefono         = $data['telefono'] ?? null;
                $admin_empresa->email            = $data['email'];
                $admin_empresa->id_empresa       = (int) $data['id_empresa'];

                if (!empty($data['password'])) {
                    $admin_empresa->password = $data['password']; // cast hashed
                }

                $admin_empresa->save();

                if (!$admin_empresa->hasRole('admin_empresa')) {
                    $admin_empresa->assignRole('admin_empresa');
                }

                return redirect()
                    ->route('admin-empresas.index')
                    ->with('success', 'Administrador actualizado.');
            });
        } catch (\Throwable $e) {
            Log::error('Error al actualizar admin_empresa', ['e' => $e]);
            $msg = app()->environment('local') ? $e->getMessage() : 'No se pudo actualizar el administrador.';
            return back()->withInput()->withErrors($msg);
        }
    }

    public function destroy(User $admin_empresa): RedirectResponse
    {
        try {
            return DB::transaction(function () use ($admin_empresa) {
                $admin_empresa->removeRole('admin_empresa');
                $admin_empresa->delete();

                return redirect()
                    ->route('admin-empresas.index')
                    ->with('success', 'Administrador eliminado.');
            });
        } catch (\Throwable $e) {
            Log::error('Error al eliminar admin_empresa', ['e' => $e]);
            $msg = app()->environment('local') ? $e->getMessage() : 'No se pudo eliminar el administrador.';
            return back()->withErrors($msg);
        }
    }
}
