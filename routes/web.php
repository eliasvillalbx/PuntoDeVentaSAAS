<?php

use Illuminate\Support\Facades\Route;

/**
 * Controladores
 * Nota: mantenemos importaciones explícitas para autocompletado y claridad.
 */
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
use App\Http\Controllers\CompraController;
use App\Http\Controllers\ChatController;


// NUEVOS controladores para billing / Clip
use App\Http\Controllers\BillingController;
use App\Http\Controllers\ClipWebhookController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
| En este archivo definimos rutas de la aplicación web (guardadas por sesión).
| - Mantén las rutas coherentes con tus middlewares y roles.
| - Evita duplicar Route::resource con el mismo prefijo/URI para no sobrescribir.
*/

/* ==================== HOME (público) ==================== */
/**
 * Si el usuario está autenticado, lo mandamos a dashboard.
 * Si no, mostramos welcome.
 */
Route::get('/', function () {
    return auth()->check()
        ? to_route('dashboard')
        : view('welcome');
})->name('home');

/* ==================== AUTH (Breeze) ==================== */
require __DIR__ . '/auth.php';

/* ==================== Webhook Clip (sin auth, sin CSRF) ==================== */
/**
 * Este endpoint lo configura Clip como webhook de Checkout.
 * No lleva auth de Laravel ni CSRF, porque lo llama el servidor de Clip.
 */
Route::post('/clip/webhook', [ClipWebhookController::class, 'handle'])
    ->name('clip.webhook')
    ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);

/* ==================== RUTAS PROTEGIDAS (auth) ==================== */
Route::middleware(['auth'])->group(function () {

    /* ---------- Dashboard (visible para cualquier usuario autenticado) ---------- */
    Route::get('/dashboard', fn () => view('dashboard'))->name('dashboard');

    /* ---------- Billing / Suscripciones básicas (sin bloqueo de suscripción) ---------- */
    // Pantalla de aviso de pago (usa BillingController@alert)
    Route::get('/billing/alert', [BillingController::class, 'alert'])
        ->name('billing.alert');

    // Crear checkout Clip (botón "Pagar con Clip" en billing.alert)
    Route::post('/billing/checkout', [BillingController::class, 'createCheckout'])
        ->name('billing.clip.checkout');

    // Activar suscripción tras pago manual (alta directa usando tu controlador actual)
    Route::post('/suscripciones', [SuscripcionController::class, 'store'])
        ->name('suscripciones.store');

    // Renovar suscripción vencida (tras pago manual)
    Route::post('/suscripciones/{suscripcion}/renew', [SuscripcionController::class, 'renew'])
        ->name('suscripciones.renew');

    /* ---------- Perfil (Breeze) ---------- */
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    /* =======================================================================
     * RUTAS COMPARTIDAS (útiles para ambos entornos: superadmin y con suscripción)
     * ======================================================================= */

    /**
     * Utilidades de compras para front (consulta de proveedores/costos por producto)
     * Disponibles para cualquier autenticado (no mutan estado).
     * Si deseas restringirlas, muévelas al grupo correspondiente.
     */
    Route::get('/productos/{producto}/proveedores-json', [CompraController::class, 'proveedoresProducto'])
        ->name('productos.proveedores_json');
    Route::get('/productos/{producto}/costo-para/{proveedor}', [CompraController::class, 'costoProductoProveedor'])
        ->name('productos.costo_para_proveedor');

    /**
     * ==================== VENTAS (UNIFICADO) ====================
     * Evitamos duplicar estas rutas en subgrupos para no romper nombres/URIs.
     * - Nombre correcto de conversión: ventas.convertirPrefactura (POST)
     * - PDF usa el método `pdf()` del controlador
     */
    Route::resource('ventas', VentaController::class)
        ->parameters(['ventas' => 'venta']);

    Route::post('ventas/{venta}/convertir', [VentaController::class, 'convertirPrefactura'])
        ->name('ventas.convertirPrefactura');

    Route::get('ventas/{venta}/pdf', [VentaController::class, 'pdf'])
        ->name('ventas.pdf');

    /* =========================================================================
     *   ZONA SUPERADMIN (SIN BLOQUEO DE SUSCRIPCIÓN)
     *   - Todo lo de gestión global: empresas, suscripciones, usuarios claves, catálogos
     * ========================================================================= */
    Route::middleware(['role:superadmin'])->group(function () {

        // Administración de compras (sin bloqueo de suscripción para SA)
        Route::resource('compras', CompraController::class);
        Route::post('/compras/{id}/recibir', [CompraController::class, 'recibir'])
            ->name('compras.recibir');

        // Catálogos
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

        // Gestión de empresas/suscripciones/usuarios de alto nivel
        Route::resource('admin-empresas', AdminEmpresaController::class)
            ->parameters(['admin-empresas' => 'admin_empresa']);

        Route::resource('empresas', EmpresaController::class);

        Route::resource('suscripciones', SuscripcionController::class)
            ->parameters(['suscripciones' => 'suscripcion']);
        // (la ruta de renew pública ya está arriba; aquí pueden coexistir vistas CRUD)

        Route::resource('gerentes', GerenteController::class)
            ->parameters(['gerentes' => 'gerente']);

        Route::resource('vendedores', VendedorController::class)
            ->parameters(['vendedores' => 'vendedor']);
    });

    /* =========================================================================
     *   ZONA APP NORMAL (CON SUSCRIPCIÓN ACTIVA)
     *   - Módulos operativos para administradores de empresa, gerentes, vendedores
     * ========================================================================= */
    Route::middleware(['suscripcion.activa'])->group(function () {



        // ==================== CHAT EMPRESARIAL ====================
        Route::get('/chat', [ChatController::class, 'index'])
            ->name('chat.index');

        Route::post('/chat/conversations', [ChatController::class, 'storeConversation'])
            ->name('chat.conversations.store');

        Route::get('/chat/conversations/{conversation}/messages', [ChatController::class, 'messages'])
            ->name('chat.messages.index');

        Route::post('/chat/conversations/{conversation}/messages', [ChatController::class, 'sendMessage'])
            ->name('chat.messages.store');
        // Compras (operativas)
        Route::resource('compras', CompraController::class);
        Route::post('/compras/{id}/recibir', [CompraController::class, 'recibir'])
            ->name('compras.recibir');

        // Empresas (si decides permitir administración a no-SA, deja este resource;
        // en caso contrario, elimínalo de aquí y deja solo el de superadmin)
        Route::resource('empresas', EmpresaController::class)
            ->names('empresas');

        // CRUDs de catálogo (operativos)
        Route::resource('categorias', CategoriaController::class)
            ->parameters(['categorias' => 'categoria']);

        Route::resource('productos', ProductoController::class)
            ->parameters(['productos' => 'producto']);

        Route::resource('proveedores', ProveedorController::class)
            ->parameters(['proveedores' => 'proveedore']);

        // Pivote: costos por proveedor en un producto (operativo)
        Route::post('productos/{producto}/proveedores', [ProductoProveedorController::class, 'store'])
            ->name('productos.proveedores.store');
        Route::put('productos/{producto}/proveedores/{proveedor}', [ProductoProveedorController::class, 'update'])
            ->name('productos.proveedores.update');
        Route::delete('productos/{producto}/proveedores/{proveedor}', [ProductoProveedorController::class, 'destroy'])
            ->name('productos.proveedores.destroy');

        // Gestión de personal operativo (si aplica)
        Route::resource('gerentes', GerenteController::class)
            ->parameters(['gerentes' => 'gerente']);

        Route::resource('vendedores', VendedorController::class)
            ->parameters(['vendedores' => 'vendedor']);

        // Clientes
        Route::resource('clientes', ClienteController::class);
    });

   
});
 Route::post('/webhooks/clip-checkout', [ClipWebhookController::class, 'handle'])
    ->name('clip.webhook');
