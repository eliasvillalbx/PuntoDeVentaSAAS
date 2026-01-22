<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Empresa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use App\Mail\UserCredentialsMail;

class UserController extends Controller
{
    /**
     * Roles existentes en el sistema (sembrados por tu seeder)
     */
    private const ROLES = [
        'superadmin',
        'administrador_empresa',
        'gerente',
        'vendedor',
    ];

    /**
     * Jerarquía EXACTA solicitada:
     * superadmin: todos
     * administrador_empresa: gerentes, vendedores
     * gerente: vendedores
     * vendedor: nada
     */
    private const CAN_MANAGE = [
        'superadmin' => ['superadmin', 'administrador_empresa', 'gerente', 'vendedor'],
        'administrador_empresa' => ['gerente', 'vendedor'],
        'gerente' => ['vendedor'],
        'vendedor' => [],
    ];

    public function __construct()
    {
        $this->middleware('auth');

        // ✅ Solo superadmin, administrador_empresa y gerente pueden acceder al módulo
        $this->middleware(function ($request, $next) {
            $u = auth()->user();

            if (!$u || !($u->hasRole('superadmin') || $u->hasRole('administrador_empresa') || $u->hasRole('gerente'))) {
                abort(403, 'No tienes permiso para acceder a la gestión de usuarios.');
            }

            return $next($request);
        });
    }

    /**
     * Devuelve el rol principal del usuario (según prioridad)
     */
    private function primaryRole(User $u): string
    {
        foreach (['superadmin', 'administrador_empresa', 'gerente', 'vendedor'] as $r) {
            if ($u->hasRole($r)) return $r;
        }
        return '';
    }

    /**
     * Qué roles puede ADMINISTRAR (crear/editar/asignar) el usuario autenticado
     */
    private function manageableRoles(User $auth): array
    {
        $role = $this->primaryRole($auth);
        return self::CAN_MANAGE[$role] ?? [];
    }

    /**
     * Qué roles puede VER en el LISTADO
     * (normalmente coincide con manageableRoles, pero lo dejamos explícito para no romper lógica)
     */
    private function visibleRoles(User $auth): array
    {
        // EXACTO a tu regla:
        // SA -> todos
        // admin_empresa -> gerentes, vendedores
        // gerente -> vendedores
        $role = $this->primaryRole($auth);

        return match ($role) {
            'superadmin' => ['superadmin', 'administrador_empresa', 'gerente', 'vendedor'],
            'administrador_empresa' => ['gerente', 'vendedor'],
            'gerente' => ['vendedor'],
            default => [],
        };
    }

    /**
     * Verifica si el usuario autenticado puede administrar a un usuario objetivo
     */
    private function canManageUser(User $auth, User $target): bool
    {
        // SA puede todo
        if ($auth->hasRole('superadmin')) return true;

        // No SA solo su empresa
        if ((int)$target->id_empresa !== (int)$auth->id_empresa) return false;

        // Rol objetivo debe estar dentro de la lista manejable
        $targetRole = $this->primaryRole($target);
        return in_array($targetRole, $this->manageableRoles($auth), true);
    }

    /**
     * Valida que el rol solicitado para asignación esté permitido según el rol del auth
     */
    private function assertCanAssignRole(User $auth, string $roleToAssign): void
    {
        if (!in_array($roleToAssign, self::ROLES, true)) {
            abort(422, 'Rol inválido.');
        }

        if (!in_array($roleToAssign, $this->manageableRoles($auth), true)) {
            abort(403, 'No tienes permiso para asignar ese rol.');
        }
    }

    public function index(Request $request)
    {
        $auth = auth()->user();
        $isSA = $auth->hasRole('superadmin');

        $query = User::with(['empresa', 'roles']);

        // --- VISIBILIDAD BASE: empresa ---
        if (!$isSA) {
            $query->where('id_empresa', $auth->id_empresa);
        }

        // --- VISIBILIDAD POR ROLES (NO por "manageable" a secas, sino por tu regla explícita) ---
        $visibleRoles = $this->visibleRoles($auth);

        if (empty($visibleRoles)) {
            $query->whereRaw('1=0');
        } else {
            $query->whereHas('roles', function ($q) use ($visibleRoles) {
                $q->whereIn('name', $visibleRoles);
            });
        }

        // --- BUSCADOR ---
        if ($request->filled('q')) {
            $search = trim($request->q);
            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                    ->orWhere('apellido_paterno', 'like', "%{$search}%")
                    ->orWhere('apellido_materno', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // --- FILTRO EMPRESA (solo SA) ---
        if ($isSA && $request->filled('empresa_id')) {
            $query->where('id_empresa', $request->empresa_id);
        }

        $users = $query->latest()->paginate(10)->withQueryString();

        $empresas = $isSA ? Empresa::orderBy('razon_social')->get() : collect();
        $miEmpresa = !$isSA ? Empresa::find($auth->id_empresa) : null;

        return view('users.index', compact('users', 'empresas', 'isSA', 'miEmpresa'));
    }

    public function create()
    {
        $auth = auth()->user();
        $isSA = $auth->hasRole('superadmin');

        // Roles disponibles según jerarquía
        $allowed = $this->manageableRoles($auth);
        $roles = Role::whereIn('name', $allowed)->orderBy('name')->get();

        $empresas = $isSA ? Empresa::orderBy('razon_social')->get() : collect();
        $miEmpresa = !$isSA ? Empresa::find($auth->id_empresa) : null;

        return view('users.create', compact('empresas', 'roles', 'isSA', 'miEmpresa'));
    }

    public function store(Request $request)
    {
        $auth = auth()->user();
        $isSA = $auth->hasRole('superadmin');

        $rules = [
            'nombre' => 'required|string|max:255',
            'apellido_paterno' => 'required|string|max:255',
            'apellido_materno' => 'nullable|string|max:255',
            'telefono' => 'nullable|string|max:30',
            'email' => 'required|email|max:255|unique:users,email',
            'role' => 'required|string',
        ];

        if ($isSA) {
            $rules['id_empresa'] = 'required|exists:empresas,id';
        }

        $validated = $request->validate($rules);

        // ✅ Autoriza rol por jerarquía
        $this->assertCanAssignRole($auth, $validated['role']);

        $idEmpresaFinal = $isSA ? (int)$request->id_empresa : (int)$auth->id_empresa;

        // ✅ Contraseña temporal
        $plainPassword = Str::password(12, true, true, true, false);

        DB::beginTransaction();

        try {
            $user = new User();
            $user->nombre = $validated['nombre'];
            $user->apellido_paterno = $validated['apellido_paterno'];
            $user->apellido_materno = $validated['apellido_materno'] ?? null;
            $user->telefono = $validated['telefono'] ?? null;
            $user->email = $validated['email'];
            $user->id_empresa = $idEmpresaFinal;
            $user->password = Hash::make($plainPassword);
            $user->save();

            $user->syncRoles([$validated['role']]);

            // ✅ Enviar credenciales (no rompe si falla)
            try {
                Mail::to($user->email)->send(new UserCredentialsMail(
                    nombre: $user->nombre,
                    email: $user->email,
                    password: $plainPassword
                ));
            } catch (\Throwable $mailEx) {
                report($mailEx);
            }

            DB::commit();

            return redirect()->route('users.index')
                ->with('success', 'Usuario creado. Se enviaron credenciales al correo.');

        } catch (QueryException $e) {
            DB::rollBack();
            if ($e->getCode() === "23000") {
                return back()->withInput()->with('error', 'No se pudo crear el usuario. El correo ya existe.');
            }
            report($e);
            return back()->withInput()->with('error', 'Error de base de datos al crear el usuario.');
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
            return back()->withInput()->with('error', 'Ocurrió un error inesperado al crear el usuario.');
        }
    }

    public function edit(User $user)
    {
        $auth = auth()->user();
        $isSA = $auth->hasRole('superadmin');

        if (!$this->canManageUser($auth, $user)) {
            abort(403, 'No tienes permiso para editar este usuario.');
        }

        $allowed = $this->manageableRoles($auth);
        $roles = Role::whereIn('name', $allowed)->orderBy('name')->get();

        $empresas = $isSA ? Empresa::orderBy('razon_social')->get() : collect();
        $miEmpresa = !$isSA ? Empresa::find($auth->id_empresa) : null;

        return view('users.edit', compact('user', 'empresas', 'roles', 'isSA', 'miEmpresa'));
    }

    public function update(Request $request, User $user)
    {
        $auth = auth()->user();
        $isSA = $auth->hasRole('superadmin');

        if (!$this->canManageUser($auth, $user)) {
            abort(403);
        }

        $rules = [
            'nombre' => 'required|string|max:255',
            'apellido_paterno' => 'required|string|max:255',
            'apellido_materno' => 'nullable|string|max:255',
            'telefono' => 'nullable|string|max:30',
            'email' => "required|email|max:255|unique:users,email,{$user->id}",
            'role' => 'required|string',
        ];

        if ($isSA) {
            $rules['id_empresa'] = 'required|exists:empresas,id';
        }

        $validated = $request->validate($rules);

        $this->assertCanAssignRole($auth, $validated['role']);

        DB::beginTransaction();

        try {
            $user->nombre = $validated['nombre'];
            $user->apellido_paterno = $validated['apellido_paterno'];
            $user->apellido_materno = $validated['apellido_materno'] ?? null;
            $user->telefono = $validated['telefono'] ?? null;
            $user->email = $validated['email'];

            if ($isSA) {
                $user->id_empresa = (int)$request->id_empresa;
            }

            $user->save();
            $user->syncRoles([$validated['role']]);

            DB::commit();

            return redirect()->route('users.index')->with('success', 'Usuario actualizado correctamente.');

        } catch (QueryException $e) {
            DB::rollBack();
            report($e);
            return back()->withInput()->with('error', 'Error de base de datos al actualizar el usuario.');
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
            return back()->withInput()->with('error', 'Ocurrió un error inesperado al actualizar el usuario.');
        }
    }
}
