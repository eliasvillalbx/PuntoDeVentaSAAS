<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Empresa;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

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

            // 2. NUEVO: Ocultar a los SuperAdmins (Solo SA pueden ver otros SA)
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
        // NUEVO: Evitar auto-eliminación
        if (auth()->id() === $user->id) {
            return back()->with('error', 'No puedes eliminar tu propia cuenta mientras estás logueado.');
        }

        $user->delete();
        return back()->with('success', 'Usuario eliminado.');
    }
}