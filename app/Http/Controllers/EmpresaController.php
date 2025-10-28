<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class EmpresaController extends Controller
{
    public function __construct()
    {
    }

    public function index(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        $empresas = Empresa::query()
            ->when($q !== '', function ($qry) use ($q) {
                $qry->where(function ($sub) use ($q) {
                    $sub->where('razon_social', 'like', "%{$q}%")
                        ->orWhere('nombre_comercial', 'like', "%{$q}%")
                        ->orWhere('rfc', 'like', "%{$q}%");
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('empresas.index', compact('empresas', 'q'));
    }

    public function create()
    {
        // Lista completa de timezones (puedes filtrar por continente si gustas)
        $timezones = \DateTimeZone::listIdentifiers();
        return view('empresas.create', compact('timezones'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'razon_social'        => ['required','string','max:200'],
            'nombre_comercial'    => ['nullable','string','max:200'],
            'rfc'                 => ['required','string','min:12','max:13','regex:/^[A-ZÑ&]{3,4}\d{6}[A-Z0-9]{3}$/i','unique:empresas,rfc'],
            'tipo_persona'        => ['required','in:moral,fisica'],
            'regimen_fiscal_code' => ['nullable','string','max:10'],
            'email'               => ['nullable','email','max:160','unique:empresas,email'],
            'telefono'            => ['nullable','string','max:20'],
            'sitio_web'           => ['nullable','string','max:200'],

            'calle'               => ['nullable','string','max:120'],
            'numero_exterior'     => ['nullable','string','max:20'],
            'numero_interior'     => ['nullable','string','max:20'],
            'colonia'             => ['nullable','string','max:120'],
            'municipio'           => ['nullable','string','max:120'],
            'ciudad'              => ['nullable','string','max:120'],
            'estado'              => ['nullable','string','max:120'],
            'pais'                => ['required','string','max:80'],      // <-- OBLIGATORIO
            'codigo_postal'       => ['nullable','string','max:10'],

            'activa'              => ['sometimes','boolean'],
            'timezone'            => ['required','timezone'],              // <-- SELECT + validación de timezone

            'logo'                => ['nullable','image','mimes:jpg,jpeg,png,webp,avif','max:2048'],
        ], [
            'razon_social.required' => 'La razón social es obligatoria.',
            'rfc.required'          => 'El RFC es obligatorio.',
            'rfc.unique'            => 'Este RFC ya está registrado.',
            'rfc.regex'             => 'El RFC no cumple con el formato válido.',
            'pais.required'         => 'El país es obligatorio.',
            'timezone.required'     => 'La zona horaria es obligatoria.',
            'timezone.timezone'     => 'Selecciona una zona horaria válida.',
            'logo.image'            => 'El archivo de logo debe ser una imagen.',
            'logo.mimes'            => 'Formatos permitidos: jpg, jpeg, png, webp, avif.',
            'logo.max'              => 'El logo no debe exceder 2 MB.',
        ]);

        try {
            $data['activa'] = $request->boolean('activa');

            if ($request->hasFile('logo')) {
                $data['logo_path'] = $request->file('logo')->store('empresas', 'public');
            }

            Empresa::create($data);

            return redirect()
                ->route('empresas.index')
                ->with('success','Empresa creada correctamente.');
        } catch (\Throwable $e) {
            Log::error('Error al crear empresa', ['e' => $e, 'trace' => $e->getTraceAsString()]);
            return back()->withInput()->withErrors('No se pudo crear la empresa.');
        }
    }

    public function show(Empresa $empresa)
    {
        return view('empresas.show', compact('empresa'));
    }

    public function edit(Empresa $empresa)
    {
        $timezones = \DateTimeZone::listIdentifiers();
        return view('empresas.edit', compact('empresa','timezones'));
    }

    public function update(Request $request, Empresa $empresa): RedirectResponse
    {
        $data = $request->validate([
            'razon_social'        => ['required','string','max:200'],
            'nombre_comercial'    => ['nullable','string','max:200'],
            'rfc'                 => [
                'required','string','min:12','max:13','regex:/^[A-ZÑ&]{3,4}\d{6}[A-Z0-9]{3}$/i',
                Rule::unique('empresas','rfc')->ignore($empresa->id),
            ],
            'tipo_persona'        => ['required','in:moral,fisica'],
            'regimen_fiscal_code' => ['nullable','string','max:10'],
            'email'               => [
                'nullable','email','max:160',
                Rule::unique('empresas','email')->ignore($empresa->id),
            ],
            'telefono'            => ['nullable','string','max:20'],
            'sitio_web'           => ['nullable','string','max:200'],

            'calle'               => ['nullable','string','max:120'],
            'numero_exterior'     => ['nullable','string','max:20'],
            'numero_interior'     => ['nullable','string','max:20'],
            'colonia'             => ['nullable','string','max:120'],
            'municipio'           => ['nullable','string','max:120'],
            'ciudad'              => ['nullable','string','max:120'],
            'estado'              => ['nullable','string','max:120'],
            'pais'                => ['required','string','max:80'],      // <-- OBLIGATORIO
            'codigo_postal'       => ['nullable','string','max:10'],

            'activa'              => ['sometimes','boolean'],
            'timezone'            => ['required','timezone'],              // <-- SELECT + validación de timezone

            'logo'                => ['nullable','image','mimes:jpg,jpeg,png,webp,avif','max:2048'],
        ], [
            'rfc.regex'           => 'El RFC no cumple con el formato válido.',
            'pais.required'       => 'El país es obligatorio.',
            'timezone.required'   => 'La zona horaria es obligatoria.',
            'timezone.timezone'   => 'Selecciona una zona horaria válida.',
            'logo.image'          => 'El archivo de logo debe ser una imagen.',
            'logo.mimes'          => 'Formatos permitidos: jpg, jpeg, png, webp, avif.',
            'logo.max'            => 'El logo no debe exceder 2 MB.',
        ]);

        try {
            $data['activa'] = $request->boolean('activa');

            if ($request->hasFile('logo')) {
                if (!empty($empresa->logo_path) && Storage::disk('public')->exists($empresa->logo_path)) {
                    Storage::disk('public')->delete($empresa->logo_path);
                }
                $data['logo_path'] = $request->file('logo')->store('empresas', 'public');
            }

            $empresa->update($data);

            return redirect()
                ->route('empresas.index')
                ->with('success','Empresa actualizada.');
        } catch (\Throwable $e) {
            Log::error('Error al actualizar empresa', ['e' => $e, 'empresa_id' => $empresa->id, 'trace' => $e->getTraceAsString()]);
            return back()->withInput()->withErrors('No se pudo actualizar la empresa.');
        }
    }

    public function destroy(Empresa $empresa): RedirectResponse
    {
        try {
            if (!empty($empresa->logo_path) && Storage::disk('public')->exists($empresa->logo_path)) {
                Storage::disk('public')->delete($empresa->logo_path);
            }

            $empresa->delete();

            return redirect()
                ->route('empresas.index')
                ->with('success','Empresa eliminada.');
        } catch (\Throwable $e) {
            Log::error('Error al eliminar empresa', ['e' => $e, 'empresa_id' => $empresa->id, 'trace' => $e->getTraceAsString()]);
            return back()->withErrors('No se pudo eliminar la empresa.');
        }
    }
}
