<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Empresa;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Throwable;

class ClienteController extends Controller
{
    public function __construct()
    {
    }

    /** INDEX: lista + filtros (incluye filtro empresa para SA) */
    public function index(Request $request): View
    {
        $user = Auth::user();

        $q        = trim((string) $request->get('q', ''));
        $tipo     = $request->get('tipo_persona'); // fisica|moral
        $activo   = $request->get('activo');       // 1|0
        $empresaQ = $request->get('empresa_id');   // solo SA puede elegir

        // Empresa efectiva a consultar
        $empresaId = $user->hasRole('superadmin')
            ? (int) ($empresaQ ?? 0)
            : (int) ($user->id_empresa ?? 0);

        $query = Cliente::query()
            ->when($empresaId, fn($qr) => $qr->where('empresa_id', $empresaId))
            ->when($tipo, fn($qr) => $qr->where('tipo_persona', $tipo))
            ->when($activo !== null && $activo !== '', fn($qr) => $qr->where('activo', (bool)$activo))
            ->when($q !== '', function ($qr) use ($q) {
                $qr->where(function ($sub) use ($q) {
                    $sub->where('nombre', 'like', "%{$q}%")
                        ->orWhere('apellido_paterno', 'like', "%{$q}%")
                        ->orWhere('apellido_materno', 'like', "%{$q}%")
                        ->orWhere('razon_social', 'like', "%{$q}%")
                        ->orWhere('rfc', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%")
                        ->orWhere('telefono', 'like', "%{$q}%")
                        ->orWhere('colonia', 'like', "%{$q}%")
                        ->orWhere('municipio', 'like', "%{$q}%")
                        ->orWhere('estado', 'like', "%{$q}%");
                });
            })
            ->latest();

        // Para no-SA, forzar scoping por su empresa si no se calculó
        if (!$user->hasRole('superadmin') && $user->id_empresa && !$empresaId) {
            $query->where('empresa_id', $user->id_empresa);
            $empresaId = (int) $user->id_empresa;
        }

        $clientes = $query->paginate(12)->withQueryString();

        // Empresas para filtro (solo SA)
        $empresas = $user->hasRole('superadmin')
            ? Empresa::orderBy('razon_social')->get(['id','razon_social','nombre_comercial'])
            : collect();

        return view('clientes.index', compact('clientes', 'q', 'tipo', 'activo', 'empresaId', 'empresas'));
    }

    /** CREATE: muestra selector de empresa para SA; para otros, fija su empresa */
    public function create(Request $request): View
    {
        $user = Auth::user();

        if ($user->hasRole('superadmin')) {
            $empresas = Empresa::orderBy('razon_social')->get(['id','razon_social','nombre_comercial']);
            return view('clientes.create', [
                'isSA'      => true,
                'empresas'  => $empresas,
                'empresaId' => null,
            ]);
        }

        $empresaId = (int) ($user->id_empresa ?? 0);
        return view('clientes.create', [
            'isSA'      => false,
            'empresas'  => collect(),
            'empresaId' => $empresaId,
        ]);
    }

    /** STORE */
    public function store(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $data = $request->validate([
            'empresa_id'      => ['required','integer','exists:empresas,id'],
            'tipo_persona'    => ['required','in:fisica,moral'],
            'nombre'          => ['nullable','string','max:255'],
            'apellido_paterno'=> ['nullable','string','max:255'],
            'apellido_materno'=> ['nullable','string','max:255'],
            'razon_social'    => ['nullable','string','max:255'],
            'rfc'             => ['nullable','string','max:13','regex:/^([A-ZÑ&]{3,4})(\d{6})([A-Z0-9]{3})?$/i'],
            'email'           => ['nullable','email','max:255'],
            'telefono'        => ['nullable','string','max:30'],
            'calle'           => ['nullable','string','max:255'],
            'numero_ext'      => ['nullable','string','max:20'],
            'numero_int'      => ['nullable','string','max:20'],
            'colonia'         => ['nullable','string','max:255'],
            'municipio'       => ['nullable','string','max:255'],
            'estado'          => ['nullable','string','max:255'],
            'cp'              => ['nullable','string','max:10'],
            'activo'          => ['nullable','boolean'],
        ], [
            'empresa_id.required' => 'La empresa es obligatoria.',
        ]);

        // Coherencia por tipo
        if ($data['tipo_persona'] === 'moral') {
            if (empty($data['razon_social'])) {
                return back()->withErrors('Para persona moral, la razón social es obligatoria.')->withInput();
            }
        } else {
            if (empty($data['nombre']) || empty($data['apellido_paterno'])) {
                return back()->withErrors('Para persona física, nombre y apellido paterno son obligatorios.')->withInput();
            }
        }

        // No-SA: solo en su empresa
        if (!$user->hasRole('superadmin') && (int)$data['empresa_id'] !== (int)$user->id_empresa) {
            return back()->withErrors('No puedes crear clientes en otra empresa.')->withInput();
        }

        try {
            DB::beginTransaction();

            // Unicidad práctica por empresa
            if (!empty($data['email'])) {
                $existsEmail = Cliente::where('empresa_id', $data['empresa_id'])
                    ->where('email', $data['email'])->exists();
                if ($existsEmail) {
                    throw new \RuntimeException('Ya existe un cliente con ese email en esta empresa.');
                }
            }
            if (!empty($data['rfc'])) {
                $existsRfc = Cliente::where('empresa_id', $data['empresa_id'])
                    ->where('rfc', $data['rfc'])->exists();
                if ($existsRfc) {
                    throw new \RuntimeException('Ya existe un cliente con ese RFC en esta empresa.');
                }
            }

            $cliente = Cliente::create($data);

            DB::commit();
            return to_route('clientes.show', $cliente)->with('status', 'Cliente creado correctamente.');
        } catch (Throwable $e) {
            DB::rollBack();
            return back()->withErrors('No se pudo crear el cliente: '.$e->getMessage())->withInput();
        }
    }

    /** SHOW: incluye empresa en la vista */
    public function show(Cliente $cliente): View
    {
        $user = Auth::user();

        if (!$user->hasRole('superadmin') && (int)$user->id_empresa !== (int)$cliente->empresa_id) {
            abort(403, 'No puedes ver clientes de otra empresa.');
        }

        $ventasCount = $cliente->ventas()->count();

        return view('clientes.show', compact('cliente', 'ventasCount'));
    }

    /** EDIT: SA puede cambiar empresa, otros no */
    public function edit(Cliente $cliente): View
    {
        $user = Auth::user();

        if (!$user->hasRole('superadmin') && (int)$user->id_empresa !== (int)$cliente->empresa_id) {
            abort(403);
        }

        $isSA = $user->hasRole('superadmin');
        $empresas = $isSA ? Empresa::orderBy('razon_social')->get(['id','razon_social','nombre_comercial']) : collect();

        return view('clientes.edit', compact('cliente','isSA','empresas'));
    }

    /** UPDATE */
    public function update(Request $request, Cliente $cliente): RedirectResponse
    {
        $user = Auth::user();

        if (!$user->hasRole('superadmin') && (int)$user->id_empresa !== (int)$cliente->empresa_id) {
            return back()->withErrors('No puedes actualizar clientes de otra empresa.');
        }

        // Si es SA puede modificar empresa_id, si no, lo forzamos al actual
        $rules = [
            'tipo_persona'    => ['required','in:fisica,moral'],
            'nombre'          => ['nullable','string','max:255'],
            'apellido_paterno'=> ['nullable','string','max:255'],
            'apellido_materno'=> ['nullable','string','max:255'],
            'razon_social'    => ['nullable','string','max:255'],
            'rfc'             => ['nullable','string','max:13','regex:/^([A-ZÑ&]{3,4})(\d{6})([A-Z0-9]{3})?$/i'],
            'email'           => ['nullable','email','max:255'],
            'telefono'        => ['nullable','string','max:30'],
            'calle'           => ['nullable','string','max:255'],
            'numero_ext'      => ['nullable','string','max:20'],
            'numero_int'      => ['nullable','string','max:20'],
            'colonia'         => ['nullable','string','max:255'],
            'municipio'       => ['nullable','string','max:255'],
            'estado'          => ['nullable','string','max:255'],
            'cp'              => ['nullable','string','max:10'],
            'activo'          => ['nullable','boolean'],
        ];
        if ($user->hasRole('superadmin')) {
            $rules['empresa_id'] = ['required','integer','exists:empresas,id'];
        }

        $data = $request->validate($rules);

        if ($data['tipo_persona'] === 'moral') {
            if (empty($data['razon_social'])) {
                return back()->withErrors('Para persona moral, la razón social es obligatoria.')->withInput();
            }
        } else {
            if (empty($data['nombre']) || empty($data['apellido_paterno'])) {
                return back()->withErrors('Para persona física, nombre y apellido paterno son obligatorios.')->withInput();
            }
        }

        try {
            DB::beginTransaction();

            // Unicidad por empresa (considerando posible cambio de empresa en SA)
            $empresaCheckId = $user->hasRole('superadmin')
                ? (int) ($data['empresa_id'] ?? $cliente->empresa_id)
                : (int) $cliente->empresa_id;

            if (!empty($data['email'])) {
                $existsEmail = Cliente::where('empresa_id', $empresaCheckId)
                    ->where('email', $data['email'])
                    ->where('id', '!=', $cliente->id)
                    ->exists();
                if ($existsEmail) {
                    throw new \RuntimeException('Ya existe un cliente con ese email en esta empresa.');
                }
            }
            if (!empty($data['rfc'])) {
                $existsRfc = Cliente::where('empresa_id', $empresaCheckId)
                    ->where('rfc', $data['rfc'])
                    ->where('id', '!=', $cliente->id)
                    ->exists();
                if ($existsRfc) {
                    throw new \RuntimeException('Ya existe un cliente con ese RFC en esta empresa.');
                }
            }

            // Forzar empresa_id para no-SA
            if (!$user->hasRole('superadmin')) {
                $data['empresa_id'] = $cliente->empresa_id;
            }

            $cliente->update($data);

            DB::commit();
            return to_route('clientes.show', $cliente)->with('status', 'Cliente actualizado correctamente.');
        } catch (Throwable $e) {
            DB::rollBack();
            return back()->withErrors('No se pudo actualizar el cliente: '.$e->getMessage())->withInput();
        }
    }

    /** DESTROY (SoftDelete) */
    public function destroy(Cliente $cliente): RedirectResponse
    {
        $user = Auth::user();

        if (!$user->hasRole('superadmin') && (int)$user->id_empresa !== (int)$cliente->empresa_id) {
            return back()->withErrors('No puedes eliminar clientes de otra empresa.');
        }

        try {
            DB::beginTransaction();

            $tieneVentas = $cliente->ventas()->exists();
            if ($tieneVentas) {
                throw new \RuntimeException('No se puede eliminar: el cliente tiene ventas asociadas.');
            }

            $cliente->delete();

            DB::commit();
            return to_route('clientes.index')->with('status', 'Cliente eliminado correctamente.');
        } catch (Throwable $e) {
            DB::rollBack();
            return back()->withErrors('No se pudo eliminar el cliente: '.$e->getMessage());
        }
    }
}
