<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * Class EmpresaController
 * * Controlador encargado de la gestión integral de las empresas (tenants) dentro de la plataforma SaaS.
 * Gestiona el ciclo de vida completo (CRUD): creación, lectura, actualización y eliminación,
 * incluyendo la validación de datos fiscales, manejo de zonas horarias y carga de logotipos.
 * * @package App\Http\Controllers
 */
class EmpresaController extends Controller
{
    public function __construct()
    {
        // Constructor reservado para middlewares o inyección de dependencias futura
        
    }

    /*
     * Muestra el listado de empresas con funcionalidad de búsqueda y paginación.
    */
    public function index(Request $request)
    {
        // Limpieza del término de búsqueda
        $q = trim((string) $request->get('q', ''));

        // Construcción de la consulta con Eloquent
        $empresas = Empresa::query()
            ->when($q !== '', function ($qry) use ($q) {
                // Búsqueda flexible por Razón Social, Nombre Comercial o RFC
                $qry->where(function ($sub) use ($q) {
                    $sub->where('razon_social', 'like', "%{$q}%")
                        ->orWhere('nombre_comercial', 'like', "%{$q}%")
                        ->orWhere('rfc', 'like', "%{$q}%");
                });
            })
            ->latest() // Ordenar por fecha de creación descendente
            ->paginate(10) // Paginación de 10 registros por página
            ->withQueryString(); // Mantiene los parámetros de búsqueda en la URL

        return view('empresas.index', compact('empresas', 'q'));
    }

    /**
     * Muestra el formulario para crear una nueva empresa.
     * Carga el listado de zonas horarias para la configuración regional.
     */
    public function create()
    {
        // Obtención de identificadores de zona horaria (ej. 'America/Mexico_City')
        $timezones = \DateTimeZone::listIdentifiers();
        return view('empresas.create', compact('timezones'));
    }

    /*
     * Almacena una nueva empresa en la base de datos.
     * Realiza validaciones estrictas de formato RFC y unicidad.
    */
    public function store(Request $request): RedirectResponse
    {
        // 1. Validación de datos de entrada
        $data = $request->validate([
            'razon_social'        => ['required','string','max:200'],
            'nombre_comercial'    => ['nullable','string','max:200'],
            // Validación de RFC con Regex oficial (Personas Físicas y Morales)
            'rfc'                 => ['required','string','min:12','max:13','regex:/^[A-ZÑ&]{3,4}\d{6}[A-Z0-9]{3}$/i','unique:empresas,rfc'],
            'tipo_persona'        => ['required','in:moral,fisica'],
            'regimen_fiscal_code' => ['nullable','string','max:10'],
            'email'               => ['nullable','email','max:160','unique:empresas,email'],
            'telefono'            => ['nullable','string','max:20'],
            'sitio_web'           => ['nullable','string','max:200'],

            // Datos de dirección
            'calle'               => ['nullable','string','max:120'],
            'numero_exterior'     => ['nullable','string','max:20'],
            'numero_interior'     => ['nullable','string','max:20'],
            'colonia'             => ['nullable','string','max:120'],
            'municipio'           => ['nullable','string','max:120'],
            'ciudad'              => ['nullable','string','max:120'],
            'estado'              => ['nullable','string','max:120'],
            'pais'                => ['required','string','max:80'],      // Dato obligatorio
            'codigo_postal'       => ['nullable','string','max:10'],

            'activa'              => ['sometimes','boolean'],
            'timezone'            => ['required','timezone'],             // Validación de existencia de la zona horaria

            // Validación de imagen (Logo)
            'logo'                => ['nullable','image','mimes:jpg,jpeg,png,webp,avif','max:2048'], // Max 2MB
        ], [
            // Mensajes de error personalizados
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
            // Conversión explícita a booleano
            $data['activa'] = $request->boolean('activa');

            // 2. Procesamiento y almacenamiento del Logo
            if ($request->hasFile('logo')) {
                // Guarda en storage/app/public/empresas
                $data['logo_path'] = $request->file('logo')->store('empresas', 'public');
            }

            // 3. Creación del registro
            Empresa::create($data);

            return redirect()
                ->route('empresas.index')
                ->with('success','Empresa creada correctamente.');
                
        } catch (\Throwable $e) {
            // Registro de errores en Log para depuración
            Log::error('Error al crear empresa', ['e' => $e, 'trace' => $e->getTraceAsString()]);
            return back()->withInput()->withErrors('No se pudo crear la empresa.');
        }
    }

    /**
     * Muestra la información detallada de una empresa específica.
     *
     * @param  Empresa  $empresa
     * @return \Illuminate\View\View
     */
    public function show(Empresa $empresa)
    {
        return view('empresas.show', compact('empresa'));
    }

    /**
     * Muestra el formulario de edición.
     *
     * @param  Empresa  $empresa
     * @return \Illuminate\View\View
     */
    public function edit(Empresa $empresa)
    {
        $timezones = \DateTimeZone::listIdentifiers();
        return view('empresas.edit', compact('empresa','timezones'));
    }

    /*
     * Actualiza los datos de una empresa existente.
     * Gestiona la unicidad del RFC excluyendo el ID actual y el reemplazo de imágenes.
    */
    public function update(Request $request, Empresa $empresa): RedirectResponse
    {
        // 1. Validación (se ignora el ID actual para evitar error de 'unique')
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
            'pais'                => ['required','string','max:80'],
            'codigo_postal'       => ['nullable','string','max:10'],

            'activa'              => ['sometimes','boolean'],
            'timezone'            => ['required','timezone'],

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

            // 2. Gestión de reemplazo de Logo
            if ($request->hasFile('logo')) {
                // Eliminar logo anterior si existe
                if (!empty($empresa->logo_path) && Storage::disk('public')->exists($empresa->logo_path)) {
                    Storage::disk('public')->delete($empresa->logo_path);
                }
                // Guardar nuevo logo
                $data['logo_path'] = $request->file('logo')->store('empresas', 'public');
            }

            // 3. Actualización del registro
            $empresa->update($data);

            return redirect()
                ->route('empresas.index')
                ->with('success','Empresa actualizada.');
        } catch (\Throwable $e) {
            Log::error('Error al actualizar empresa', ['e' => $e, 'empresa_id' => $empresa->id, 'trace' => $e->getTraceAsString()]);
            return back()->withInput()->withErrors('No se pudo actualizar la empresa.');
        }
    }

    /**
     * Elimina una empresa y toda su información asociada (Usuarios, Ventas, etc.).
     * Utiliza una transacción para asegurar que se borre todo o nada.
     */
    public function destroy(Empresa $empresa): RedirectResponse
    {
        // Inicia una transacción de base de datos para seguridad
        DB::beginTransaction();

        try {
            // 1. Limpieza de archivos físicos
            // Elimina el logotipo del servidor si existe
            if (!empty($empresa->logo_path) && Storage::disk('public')->exists($empresa->logo_path)) {
                Storage::disk('public')->delete($empresa->logo_path);
            }

            // 2. Eliminación de datos dependientes (Cascada)
            // Se eliminan los empleados y ventas para evitar errores de integridad
            $empresa->users()->delete();
            $empresa->ventas()->delete();
            // (El sistema eliminará automáticamente el resto de relaciones configuradas)

            // 3. Eliminación de la empresa
            $empresa->delete();

            // Confirma los cambios en la base de datos
            DB::commit();

            return redirect()
                ->route('empresas.index')
                ->with('success','Empresa y sus datos eliminados correctamente.');

        } catch (\Throwable $e) {
            // Si algo falla, deshace todos los cambios para no dañar los datos
            DB::rollBack();
            Log::error('Error al eliminar empresa', ['e' => $e]);
            
            return back()->withErrors('No se pudo eliminar: La empresa tiene datos activos.');
        }
    }
}