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

class VendedorController extends Controller
{
    public function __construct()
    {

        // Solo SA, AE o Gerente pueden acceder a cualquier acción del módulo
        $onlyAllowed = function ($request, $next) {
            $u = Auth::user();
            if (!$u->hasAnyRole(['superadmin', 'administrador_empresa', 'gerente'])) {
                return redirect()->route('dashboard')
                    ->with('error', 'No tienes permisos para acceder a vendedores.');
            }
            return $next($request);
        };

    }

    /**
     * Listado de vendedores (rol: empleado).
     * SA: puede filtrar por empresa. AE/Gerente: sólo su empresa.
     * Blade espera: $vendedores, $q, $empresas (solo SA), $empresaId
     */
    public function index(Request $request)
    {
        $auth = Auth::user();
        $isSA = $auth->hasRole('superadmin');

        $q         = trim((string) $request->get('q', ''));
        $empresaId = $request->integer('empresa_id') ?: null;

        $query = User::query()
            ->whereHas('roles', fn ($r) => $r->where('name', 'empleado')) // "vendedor"
            ->when(!$isSA, fn ($qry) => $qry->where('id_empresa', $auth->id_empresa))
            ->when($isSA && $empresaId, fn ($qry) => $qry->where('id_empresa', $empresaId))
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

        $vendedores = $query->paginate(12)->withQueryString();

        $empresas = $isSA
            ? Empresa::orderBy('razon_social')->get(['id', 'razon_social'])
            : collect();

        return view('vendedores.index', compact('vendedores', 'q', 'empresas', 'empresaId'));
    }

    /**
     * Form de creación.
     * SA: ve selector de empresa; AE/Gerente: no.
     * Blade espera: $empresas (solo SA)
     */
    public function create()
    {
        $auth = Auth::user();
        $empresas = $auth->hasRole('superadmin')
            ? Empresa::orderBy('razon_social')->get(['id', 'razon_social'])
            : collect();

        return view('vendedores.create', compact('empresas'));
    }

    /**
     * Guardar nuevo vendedor (rol: empleado).
     * SA: puede elegir empresa. AE/Gerente: se fuerza id_empresa de la sesión.
     */
    public function store(Request $request): RedirectResponse
    {
        $auth  = Auth::user();
        $isSA  = $auth->hasRole('superadmin');
        $isAE  = $auth->hasRole('administrador_empresa');
        $isGer = $auth->hasRole('gerente');

        if (!$isSA && !$isAE && !$isGer) {
            return back()->withInput()->with('error', 'No tienes permisos para crear vendedores.');
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

        // AE y Gerente: forzar su empresa
        if ($isAE || $isGer) {
            $data['id_empresa'] = $auth->id_empresa;
        }

        DB::beginTransaction();
        try {
            $vendedor = new User();
            $vendedor->fill([
                'nombre'            => $data['nombre'],
                'apellido_paterno'  => $data['apellido_paterno'],
                'apellido_materno'  => $data['apellido_materno'] ?? null,
                'telefono'          => $data['telefono'] ?? null,
                'email'             => $data['email'],
                'password'          => $data['password'], // cast 'hashed' encripta automáticamente
                'id_empresa'        => $data['id_empresa'],
            ]);
            $vendedor->save();

            // Asigna rol "empleado" (vendedor)
            $vendedor->syncRoles(['empleado']);

            DB::commit();
            return redirect()->route('vendedores.index')->with('success', 'Vendedor creado correctamente.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error al crear vendedor', [
                'msg'  => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return back()->withInput()->with('error', 'Ocurrió un error al crear el vendedor. Intenta de nuevo.');
        }
    }

    /**
     * Mostrar detalle.
     * Blade espera: $vendedor
     */
    public function show(User $vendedor)
    {
        $auth = Auth::user();

        if (!$vendedor->hasRole('empleado')) {
            return redirect()->route('vendedores.index')->with('error', 'El usuario seleccionado no es un vendedor.');
        }

        if (!$auth->hasRole('superadmin') && $vendedor->id_empresa !== $auth->id_empresa) {
            return redirect()->route('vendedores.index')->with('error', 'No puedes ver vendedores de otra empresa.');
        }

        return view('vendedores.show', compact('vendedor'));
    }

    /**
     * Form de edición.
     * Blade espera: $vendedor, $empresas (solo SA)
     */
    public function edit(User $vendedor)
    {
        $auth = Auth::user();
        $isSA = $auth->hasRole('superadmin');

        if (!$vendedor->hasRole('empleado')) {
            return redirect()->route('vendedores.index')->with('error', 'El usuario seleccionado no es un vendedor.');
        }

        if (!$isSA && $vendedor->id_empresa !== $auth->id_empresa) {
            return redirect()->route('vendedores.index')->with('error', 'No puedes editar vendedores de otra empresa.');
        }

        $empresas = $isSA
            ? Empresa::orderBy('razon_social')->get(['id', 'razon_social'])
            : collect();

        return view('vendedores.edit', compact('vendedor', 'empresas'));
    }

    /**
     * Actualizar vendedor.
     * SA: puede cambiar empresa. AE/Gerente: se fuerza su empresa.
     */
    public function update(Request $request, User $vendedor): RedirectResponse
    {
        $auth  = Auth::user();
        $isSA  = $auth->hasRole('superadmin');
        $isAE  = $auth->hasRole('administrador_empresa');
        $isGer = $auth->hasRole('gerente');

        if (!$isSA && !$isAE && !$isGer) {
            return redirect()->route('vendedores.index')->with('error', 'No tienes permisos para actualizar vendedores.');
        }

        if (!$vendedor->hasRole('empleado')) {
            return redirect()->route('vendedores.index')->with('error', 'El usuario seleccionado no es un vendedor.');
        }

        if (!$isSA && $vendedor->id_empresa !== $auth->id_empresa) {
            return redirect()->route('vendedores.index')->with('error', 'No puedes actualizar vendedores de otra empresa.');
        }

        $rules = [
            'nombre'            => ['required', 'string', 'max:100'],
            'apellido_paterno'  => ['required', 'string', 'max:100'],
            'apellido_materno'  => ['nullable', 'string', 'max:100'],
            'telefono'          => ['nullable', 'string', 'max:30'],
            'email'             => [
                'required', 'email', 'max:180',
                Rule::unique('users', 'email')->ignore($vendedor->id),
            ],
            'password'          => ['nullable', 'string', 'min:8', 'confirmed'],
            'id_empresa'        => [$isSA ? 'required' : 'nullable', 'integer', 'exists:empresas,id'],
        ];

        $data = $request->validate($rules);

        if ($isAE || $isGer) {
            $data['id_empresa'] = $auth->id_empresa;
        }

        DB::beginTransaction();
        try {
            $vendedor->fill([
                'nombre'            => $data['nombre'],
                'apellido_paterno'  => $data['apellido_paterno'],
                'apellido_materno'  => $data['apellido_materno'] ?? null,
                'telefono'          => $data['telefono'] ?? null,
                'email'             => $data['email'],
                'id_empresa'        => $data['id_empresa'],
            ]);

            if (!empty($data['password'])) {
                $vendedor->password = $data['password']; // cast 'hashed'
            }

            $vendedor->save();

            // Por si acaso: garantizar que mantenga el rol "empleado"
            if (!$vendedor->hasRole('empleado')) {
                $vendedor->syncRoles(['empleado']);
            }

            DB::commit();
            return redirect()->route('vendedores.index')->with('success', 'Vendedor actualizado correctamente.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error al actualizar vendedor', [
                'msg'        => $e->getMessage(),
                'file'       => $e->getFile(),
                'line'       => $e->getLine(),
                'vendedor_id'=> $vendedor->id,
            ]);
            return back()->withInput()->with('error', 'Ocurrió un error al actualizar el vendedor. Intenta de nuevo.');
        }
    }

    /**
     * Eliminar vendedor.
     */
    public function destroy(User $vendedor): RedirectResponse
    {
        $auth  = Auth::user();
        $isSA  = $auth->hasRole('superadmin');
        $isAE  = $auth->hasRole('administrador_empresa');
        $isGer = $auth->hasRole('gerente');

        if (!$isSA && !$isAE && !$isGer) {
            return back()->with('error', 'No tienes permisos para eliminar vendedores.');
        }

        if (!$vendedor->hasRole('empleado')) {
            return redirect()->route('vendedores.index')->with('error', 'El usuario seleccionado no es un vendedor.');
        }

        if ($vendedor->id === $auth->id) {
            return back()->with('error', 'No puedes eliminar tu propio usuario.');
        }

        if (!$isSA && $vendedor->id_empresa !== $auth->id_empresa) {
            return back()->with('error', 'No puedes eliminar vendedores de otra empresa.');
        }

        DB::beginTransaction();
        try {
            // Limpia roles si tu config no hace cascade
            $vendedor->syncRoles([]);
            $vendedor->delete();

            DB::commit();
            return redirect()->route('vendedores.index')->with('success', 'Vendedor eliminado correctamente.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error al eliminar vendedor', [
                'msg'        => $e->getMessage(),
                'file'       => $e->getFile(),
                'line'       => $e->getLine(),
                'vendedor_id'=> $vendedor->id,
            ]);
            return back()->with('error', 'Ocurrió un error al eliminar el vendedor. Intenta de nuevo.');
        }
    }
}
