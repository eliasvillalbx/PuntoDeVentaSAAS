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

        // Filtro por Rol de visualización
        if (!$isSA) {
            $query->deEmpresa($user->id_empresa);
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
        $roles = Role::all(); // O filtrar los roles que un admin puede asignar
        
        return view('users.create', compact('empresas', 'roles', 'isSA'));
    }

    public function store(Request $request)
    {
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
        $roles = Role::all();
        
        return view('users.edit', compact('user', 'empresas', 'roles', 'isSA'));
    }

    public function update(Request $request, User $user)
    {
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
        $user->delete();
        return back()->with('success', 'Usuario eliminado.');
    }
}