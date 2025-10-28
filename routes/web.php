<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EmpresaController;
use App\Http\Controllers\SuscripcionController;
use App\Http\Controllers\ProfileController;

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

        // Rutas solo para superadmin (o sin bloqueo de suscripción)
        Route::resource('empresas', EmpresaController::class);
    });

    /* =========================================================
     *   ZONA APP NORMAL (CON SUSCRIPCIÓN ACTIVA)
     *   Para admin_empresa, gerente, empleado, etc.
     * ========================================================= */
    Route::middleware(['suscripcion.activa'])->group(function () {
        Route::get('/dashboard', fn () => view('dashboard'))->name('dashboard');

        // Si quieres que NO superadmin gestionen empresas, déjalo aquí:
        Route::resource('empresas', EmpresaController::class)->names('empresas');

        // ...más rutas protegidas por suscripción
        // Route::resource('clientes', ClienteController::class);
        // Route::get('/reportes', [ReporteController::class, 'index'])->name('reportes.index');
    });
});
