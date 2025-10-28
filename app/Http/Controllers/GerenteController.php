<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Empresa;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class GerenteController extends Controller
{
    public function __construct()
    {

        // Solo SA o AE pueden crear/editar/eliminar
        $onlySaOrAe = function ($request, $next) {
            $u = Auth::user();
            if (!$u->hasAnyRole(['superadmin', 'administrador_empresa'])) {
                return redirect()->route('gerentes.index')
                    ->with('error', 'No tienes permisos para realizar esta acción.');
            }
            return $next($request);
        };

    }

    /**
     * Listado de gerentes (con búsqueda y filtro por empresa para SA).
     * Variables para Blade (similar a tu diseño): $gerentes, $q, $empresas, $empresaId
     */
    public function index(Request $request)
    {
        $auth = Auth::user();

        if (!$auth->hasAnyRole(['superadmin', 'administrador_empresa'])) {
            return redirect()->route('dashboard')->with('error', 'No tienes permisos para acceder a gerentes.');
        }

        $q         = trim((string) $request->get('q', ''));
        $empresaId = $request->integer('empresa_id') ?: null;

        $query = User::query()
            ->whereHas('roles', fn ($r) => $r->where('name', 'gerente'))
            ->when(!$auth->hasRole('superadmin'), fn ($qry) => $qry->where('id_empresa', $auth->id_empresa))
            ->when($auth->hasRole('superadmin') && $empresaId, fn ($qry) => $qry->where('id_empresa', $empresaId))
            ->when($q !== '', function ($qry) use ($q) {
                $qry->where(function ($sub) use ($q) {
                    $sub->where('nombre', 'like', "%{$q}%")
                        ->orWhere('apellido_paterno', 'like', "%{$q}%")
                        ->orWhere('apellido_materno', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%")
                        ->orWhere('telefono', 'like', "%{$q}%");
                });
            })
            ->latest();

        $gerentes = $query->paginate(12)->withQueryString();

        // Catálogo de empresas para filtro (solo SA)
        $empresas = $auth->hasRole('superadmin')
            ? Empresa::orderBy('razon_social')->get(['id', 'razon_social'])
            : collect();

        return view('gerentes.index', compact('gerentes', 'q', 'empresas', 'empresaId'));
    }

    /**
     * Form de creación (SA puede elegir empresa; AE se fuerza a su empresa)
     * Variables: $empresas (solo para SA)
     */
    public function create()
    {
        $auth = Auth::user();

        $empresas = $auth->hasRole('superadmin')
            ? Empresa::orderBy('razon_social')->get(['id', 'razon_social'])
            : collect();

        return view('gerentes.create', compact('empresas'));
    }

    /**
     * Persistir gerente nuevo
     */
    public function store(Request $request): RedirectResponse
    {
        $auth = Auth::user();
        $isSA = $auth->hasRole('superadmin');
        $isAE = $auth->hasRole('administrador_empresa');

        if (!$isSA && !$isAE) {
            return back()->withInput()->with('error', 'No tienes permisos para crear gerentes.');
        }

        $rules = [
            'nombre'            => ['required', 'string', 'max:100'],
            'apellido_paterno'  => ['required', 'string', 'max:100'],
            'apellido_materno'  => ['nullable', 'string', 'max:100'],
            'telefono'          => ['nullable', 'string', 'max:30'],
            'email'             => ['required', 'email', 'max:180', 'unique:users,email'],
            'password'          => ['required', 'string', 'min:8', 'confirmed'],
            'id_empresa'        => [$isSA ? 'required' : 'nullable', 'integer', 'exists:empresas,id'],
        ];

        $data = $request->validate($rules);

        // AE: fuerza su propia empresa
        if ($isAE) {
            $data['id_empresa'] = $auth->id_empresa;
        }

        DB::beginTransaction();
        try {
            $gerente = new User();
            $gerente->fill([
                'nombre'            => $data['nombre'],
                'apellido_paterno'  => $data['apellido_paterno'],
                'apellido_materno'  => $data['apellido_materno'] ?? null,
                'telefono'          => $data['telefono'] ?? null,
                'email'             => $data['email'],
                'password'          => $data['password'], // cast 'hashed' encripta en Laravel 12
                'id_empresa'        => $data['id_empresa'],
            ]);
            $gerente->save();

            // Asegurar rol
            $gerente->syncRoles(['gerente']);

            DB::commit();
            return redirect()->route('gerentes.index')->with('success', 'Gerente creado correctamente.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error al crear gerente', [
                'msg'  => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return back()->withInput()->with('error', 'Ocurrió un error al crear el gerente. Intenta de nuevo.');
        }
    }

    /**
     * Mostrar detalle de un gerente
     * Variables: $gerente
     */
    public function show(User $gerente)
    {
        $auth = Auth::user();

        if (!$gerente->hasRole('gerente')) {
            return redirect()->route('gerentes.index')->with('error', 'El usuario seleccionado no es un gerente.');
        }

        if ($auth->hasRole('administrador_empresa') && $gerente->id_empresa !== $auth->id_empresa) {
            return redirect()->route('gerentes.index')->with('error', 'No puedes ver gerentes de otra empresa.');
        }

        return view('gerentes.show', compact('gerente'));
    }

    /**
     * Form de edición
     * Variables: $gerente, $empresas (solo SA)
     */
    public function edit(User $gerente)
    {
        $auth = Auth::user();

        if (!$gerente->hasRole('gerente')) {
            return redirect()->route('gerentes.index')->with('error', 'El usuario seleccionado no es un gerente.');
        }

        if ($auth->hasRole('administrador_empresa') && $gerente->id_empresa !== $auth->id_empresa) {
            return redirect()->route('gerentes.index')->with('error', 'No puedes editar gerentes de otra empresa.');
        }

        $empresas = $auth->hasRole('superadmin')
            ? Empresa::orderBy('razon_social')->get(['id', 'razon_social'])
            : collect();

        return view('gerentes.edit', compact('gerente', 'empresas'));
    }

    /**
     * Actualizar gerente
     */
    public function update(Request $request, User $gerente): RedirectResponse
    {
        $auth = Auth::user();
        $isSA = $auth->hasRole('superadmin');
        $isAE = $auth->hasRole('administrador_empresa');

        if (!$isSA && !$isAE) {
            return redirect()->route('gerentes.index')->with('error', 'No tienes permisos para actualizar gerentes.');
        }

        if (!$gerente->hasRole('gerente')) {
            return redirect()->route('gerentes.index')->with('error', 'El usuario seleccionado no es un gerente.');
        }

        if ($isAE && $gerente->id_empresa !== $auth->id_empresa) {
            return redirect()->route('gerentes.index')->with('error', 'No puedes actualizar gerentes de otra empresa.');
        }

        $rules = [
            'nombre'            => ['required', 'string', 'max:100'],
            'apellido_paterno'  => ['required', 'string', 'max:100'],
            'apellido_materno'  => ['nullable', 'string', 'max:100'],
            'telefono'          => ['nullable', 'string', 'max:30'],
            'email'             => [
                'required', 'email', 'max:180',
                Rule::unique('users', 'email')->ignore($gerente->id),
            ],
            'password'          => ['nullable', 'string', 'min:8', 'confirmed'],
            'id_empresa'        => [$isSA ? 'required' : 'nullable', 'integer', 'exists:empresas,id'],
        ];

        $data = $request->validate($rules);

        if ($isAE) {
            $data['id_empresa'] = $auth->id_empresa;
        }

        DB::beginTransaction();
        try {
            $gerente->fill([
                'nombre'            => $data['nombre'],
                'apellido_paterno'  => $data['apellido_paterno'],
                'apellido_materno'  => $data['apellido_materno'] ?? null,
                'telefono'          => $data['telefono'] ?? null,
                'email'             => $data['email'],
                'id_empresa'        => $data['id_empresa'],
            ]);

            if (!empty($data['password'])) {
                $gerente->password = $data['password']; // cast 'hashed'
            }

            $gerente->save();

            // Reforzar que mantenga el rol gerente
            if (!$gerente->hasRole('gerente')) {
                $gerente->syncRoles(['gerente']);
            }

            DB::commit();
            return redirect()->route('gerentes.index')->with('success', 'Gerente actualizado correctamente.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error al actualizar gerente', [
                'msg'        => $e->getMessage(),
                'file'       => $e->getFile(),
                'line'       => $e->getLine(),
                'gerente_id' => $gerente->id,
            ]);
            return back()->withInput()->with('error', 'Ocurrió un error al actualizar el gerente. Intenta de nuevo.');
        }
    }

    /**
     * Eliminar gerente
     */
    public function destroy(User $gerente): RedirectResponse
    {
        $auth = Auth::user();
        $isSA = $auth->hasRole('superadmin');
        $isAE = $auth->hasRole('administrador_empresa');

        if (!$isSA && !$isAE) {
            return back()->with('error', 'No tienes permisos para eliminar gerentes.');
        }

        if (!$gerente->hasRole('gerente')) {
            return redirect()->route('gerentes.index')->with('error', 'El usuario seleccionado no es un gerente.');
        }

        if ($gerente->id === $auth->id) {
            return back()->with('error', 'No puedes eliminar tu propio usuario.');
        }

        if ($isAE && $gerente->id_empresa !== $auth->id_empresa) {
            return back()->with('error', 'No puedes eliminar gerentes de otra empresa.');
        }

        DB::beginTransaction();
        try {
            $gerente->syncRoles([]); // limpia roles si no hay cascade
            $gerente->delete();

            DB::commit();
            return redirect()->route('gerentes.index')->with('success', 'Gerente eliminado correctamente.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error al eliminar gerente', [
                'msg'        => $e->getMessage(),
                'file'       => $e->getFile(),
                'line'       => $e->getLine(),
                'gerente_id' => $gerente->id,
            ]);
            return back()->with('error', 'Ocurrió un error al eliminar el gerente. Intenta de nuevo.');
        }
    }
}
