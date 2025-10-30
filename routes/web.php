<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EmpresaController;
use App\Http\Controllers\SuscripcionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AdminEmpresaController;
use App\Http\Controllers\GerenteController;
use App\Http\Controllers\VendedorController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\ProveedorController;
use App\Http\Controllers\ProductoProveedorController;
use App\Http\Controllers\VentaController;
use App\Http\Controllers\ClienteController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Home: si está autenticado manda a dashboard; si no, welcome
Route::get('/', function () {
    return auth()->check()
        ? to_route('dashboard')
        : view('welcome');
})->name('home');

/* ==================== AUTH (Breeze) ==================== */
require __DIR__ . '/auth.php';

/* ==================== RUTAS PROTEGIDAS (auth) ==================== */
Route::middleware(['auth'])->group(function () {

    /* ---------- Billing / Suscripciones (sin bloqueo de suscripción) ---------- */

    // Pantalla de aviso de pago
    Route::get('/billing/alert', function () {
        return view('billing.alert');
    })->name('billing.alert');

    // Activar suscripción tras pago (alta)
    Route::post('/suscripciones', [SuscripcionController::class, 'store'])
        ->name('suscripciones.store');

    // Renovar suscripción vencida (tras pago)
    Route::post('/suscripciones/{suscripcion}/renew', [SuscripcionController::class, 'renew'])
        ->name('suscripciones.renew');

    // Perfil (Breeze) — opcional
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    /* =========================================================
     *   ZONA SUPERADMIN (SIN SUSCRIPCIÓN)
     * ========================================================= */
    Route::middleware(['role:superadmin'])->group(function () {
        Route::get('/dashboard', fn () => view('dashboard'))->name('dashboard');

        Route::resource('categorias', CategoriaController::class)
        ->parameters(['categorias' => 'categoria']);

    Route::resource('productos', ProductoController::class)
        ->parameters(['productos' => 'producto']);

    Route::resource('proveedores', ProveedorController::class)
        ->parameters(['proveedores' => 'proveedore']); // {proveedore} por convención

    // Pivote: costos por proveedor en un producto
    Route::post('productos/{producto}/proveedores', [ProductoProveedorController::class, 'store'])
        ->name('productos.proveedores.store');

    Route::put('productos/{producto}/proveedores/{proveedor}', [ProductoProveedorController::class, 'update'])
        ->name('productos.proveedores.update');

    Route::delete('productos/{producto}/proveedores/{proveedor}', [ProductoProveedorController::class, 'destroy'])
        ->name('productos.proveedores.destroy');

        Route::resource('admin-empresas', AdminEmpresaController::class)
        ->parameters(['admin-empresas' => 'admin_empresa']); 
        // Rutas solo para superadmin (o sin bloqueo de suscripción)
        Route::resource('empresas', EmpresaController::class);
        Route::resource('suscripciones', \App\Http\Controllers\SuscripcionController::class)
        ->parameters(['suscripciones' => 'suscripcion']);
        // Renovar
        Route::post('suscripciones/{suscripcion}/renew', [SuscripcionController::class, 'renew'])
            ->name('suscripciones.renew');

        Route::resource('gerentes', \App\Http\Controllers\GerenteController::class)
        ->parameters(['gerentes' => 'gerente']);    

        Route::resource('vendedores', \App\Http\Controllers\VendedorController::class)
        ->parameters(['vendedores' => 'vendedor'])
        ->middleware(['auth','verified']);



        Route::resource('ventas', VentaController::class);
    Route::post('ventas/{venta}/convertir', [VentaController::class, 'convertirPrefactura'])->name('ventas.convertir');

    // Opcional PDF:
    Route::get('ventas/{venta}/pdf', [VentaController::class, 'exportPdf'])->name('ventas.pdf');
    });

    /* =========================================================
     *   ZONA APP NORMAL (CON SUSCRIPCIÓN ACTIVA)
     *   Para admin_empresa, gerente, empleado, etc.
     * ========================================================= */
    Route::middleware(['suscripcion.activa'])->group(function () {
        Route::get('/dashboard', fn () => view('dashboard'))->name('dashboard');

        // Si quieres que NO superadmin gestionen empresas, déjalo aquí:
        Route::resource('empresas', EmpresaController::class)->names('empresas');
        Route::resource('suscripciones', \App\Http\Controllers\SuscripcionController::class)
        ->parameters(['suscripciones' => 'suscripcion']);

        Route::resource('gerentes', \App\Http\Controllers\GerenteController::class)
        ->parameters(['gerentes' => 'gerente']);


        Route::resource('vendedores', \App\Http\Controllers\VendedorController::class)
            ->parameters(['vendedores' => 'vendedor'])
            ->middleware(['auth','verified']);


            Route::resource('categorias', CategoriaController::class)
        ->parameters(['categorias' => 'categoria']);

    Route::resource('productos', ProductoController::class)
        ->parameters(['productos' => 'producto']);

    Route::resource('proveedores', ProveedorController::class)
        ->parameters(['proveedores' => 'proveedore']); // {proveedore} por convención

    // Pivote: costos por proveedor en un producto
    Route::post('productos/{producto}/proveedores', [ProductoProveedorController::class, 'store'])
        ->name('productos.proveedores.store');

    Route::put('productos/{producto}/proveedores/{proveedor}', [ProductoProveedorController::class, 'update'])
        ->name('productos.proveedores.update');

    Route::delete('productos/{producto}/proveedores/{proveedor}', [ProductoProveedorController::class, 'destroy'])
        ->name('productos.proveedores.destroy');


        Route::resource('ventas', VentaController::class);
    Route::post('ventas/{venta}/convertir', [VentaController::class, 'convertirPrefactura'])->name('ventas.convertir');

    // Opcional PDF:
    Route::get('ventas/{venta}/pdf', [VentaController::class, 'exportPdf'])->name('ventas.pdf');
    Route::resource('clientes', ClienteController::class);

    
        // ...más rutas protegidas por suscripción
        // Route::resource('clientes', ClienteController::class);
        // Route::get('/reportes', [ReporteController::class, 'index'])->name('reportes.index');
    });
});
