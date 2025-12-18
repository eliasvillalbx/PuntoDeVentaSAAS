<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Empresa;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB; // <--- Importante para transacciones
use Illuminate\Database\QueryException;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $isSA = $user->hasRole('superadmin');

        $query = User::with(['empresa', 'roles']);

        // --- LÓGICA DE VISIBILIDAD ---
        if (!$isSA) {
            // 1. Solo ver usuarios de mi empresa
            $query->deEmpresa($user->id_empresa);

            // 2. Ocultar a los SuperAdmins (Solo SA pueden ver otros SA)
            $query->whereDoesntHave('roles', function ($q) {
                $q->where('name', 'superadmin');
            });
        }

        // Buscador
        if ($request->q) {
            $query->where(function($q) use ($request) {
                $q->where('nombre', 'like', "%{$request->q}%")
                  ->orWhere('apellido_paterno', 'like', "%{$request->q}%")
                  ->orWhere('email', 'like', "%{$request->q}%");
            });
        }

        // Filtro por Empresa (Solo SA)
        if ($isSA && $request->empresa_id) {
            $query->where('id_empresa', $request->empresa_id);
        }

        $users = $query->latest()->paginate(10);
        $empresas = $isSA ? Empresa::all() : [];

        return view('users.index', compact('users', 'empresas', 'isSA'));
    }

    public function create()
    {
        $isSA = auth()->user()->hasRole('superadmin');
        $empresas = $isSA ? Empresa::all() : [];
        
        // Filtrar roles: Si no es SA, quitamos la opción de crear un 'superadmin'
        $roles = Role::all();
        if (!$isSA) {
            $roles = $roles->reject(fn($r) => $r->name === 'superadmin');
        }
        
        return view('users.create', compact('empresas', 'roles', 'isSA'));
    }

    public function store(Request $request)
    {
        // Validación extra: Si no es SA y trata de inyectar el rol superadmin, abortamos
        if (!auth()->user()->hasRole('superadmin') && $request->role === 'superadmin') {
            abort(403, 'No tienes permiso para crear Super Administradores.');
        }

        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'apellido_paterno' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8',
            'id_empresa' => auth()->user()->hasRole('superadmin') ? 'required' : 'nullable',
            'role' => 'required'
        ]);

        $validated['id_empresa'] = auth()->user()->hasRole('superadmin') 
            ? $request->id_empresa 
            : auth()->user()->id_empresa;

        $user = User::create($validated);
        $user->assignRole($request->role);

        return redirect()->route('users.index')->with('success', 'Usuario creado.');
    }

    public function edit(User $user)
    {
        // Seguridad: No editar usuarios de otra empresa si no es SA
        if (!auth()->user()->hasRole('superadmin') && $user->id_empresa !== auth()->user()->id_empresa) {
            abort(403);
        }

        $isSA = auth()->user()->hasRole('superadmin');
        $empresas = $isSA ? Empresa::all() : [];
        
        // Filtrar roles en edición también
        $roles = Role::all();
        if (!$isSA) {
            $roles = $roles->reject(fn($r) => $r->name === 'superadmin');
        }
        
        return view('users.edit', compact('user', 'empresas', 'roles', 'isSA'));
    }

    public function update(Request $request, User $user)
    {
        // Validación extra de seguridad de rol
        if (!auth()->user()->hasRole('superadmin') && $request->role === 'superadmin') {
            abort(403);
        }

        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'email' => "required|email|unique:users,email,{$user->id}",
            'role' => 'required'
        ]);

        if ($request->filled('password')) {
            $validated['password'] = $request->password;
        } else {
            unset($validated['password']);
        }

        $user->update($validated);
        $user->syncRoles($request->role);

        return redirect()->route('users.index')->with('success', 'Usuario actualizado.');
    }

    public function destroy(User $user)
    {
        // 1. Evitar auto-eliminación
        if (auth()->id() === $user->id) {
            return back()->with('error', 'No puedes eliminar tu propia cuenta mientras estás logueado.');
        }

        // 2. INICIAMOS TRANSACCIÓN
        // Esto asegura que si falla el delete, se revierta cualquier cambio en roles
        DB::beginTransaction();

        try {
            // Intentamos eliminar
            $user->delete();

            // Si llegamos aquí, todo salió bien
            DB::commit();
            
            return back()->with('success', 'Usuario eliminado correctamente.');

        } catch (QueryException $e) {
            // REVERTIMOS CAMBIOS (Devuelve los roles si se hubieran quitado)
            DB::rollBack();

            // Error 1451: Integridad referencial (tiene compras/ventas asociadas)
            if ($e->getCode() == "23000") {
                return back()->with('error', 'No se puede eliminar el usuario porque tiene registros asociados (ventas, compras, etc.). Sus roles han sido restaurados.');
            }

            return back()->with('error', 'Ocurrió un error de base de datos inesperado.');
            
        } catch (\Exception $e) {
            // Cualquier otro error
            DB::rollBack();
            return back()->with('error', 'Ocurrió un error inesperado: ' . $e->getMessage());
        }
    }
}