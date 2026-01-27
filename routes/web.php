<?php

use Illuminate\Support\Facades\Route;

/**
 * ==================================================================================
 * IMPORTACIÓN DE CONTROLADORES
 * ==================================================================================
 */
use App\Http\Controllers\EmpresaController;
use App\Http\Controllers\SuscripcionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
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
use App\Http\Controllers\CalendarEventController;
use App\Http\Controllers\UserController;

// Controladores de utilidades y pagos
use App\Http\Controllers\BillingController;
use App\Http\Controllers\ClipWebhookController;
use App\Http\Controllers\StripeWebhookController; // ✅ AGREGADO (Stripe)
use App\Http\Controllers\BackupController;
use App\Http\Controllers\ReporteController; // <--- Importación correcta (Singular)

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

/* ==================== HOME (público) ==================== */
Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return view('welcome');
})->name('home');

/* ==================== AUTH (Breeze) ==================== */
require __DIR__ . '/auth.php';

/* ==================== Webhooks Clip (Sin Auth) ==================== */
Route::post('/clip/webhook', [ClipWebhookController::class, 'handle'])
    ->name('clip.webhook')
    ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);

// Webhook secundario (preservado de tu código original)
Route::post('/webhooks/clip-checkout', [ClipWebhookController::class, 'handle'])
    ->name('clip.webhook.alt'); // Cambié nombre levemente para evitar colisión si usas cache de rutas

/* ==================== Webhooks Stripe (Sin Auth) ==================== */
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handle'])
    ->name('stripe.webhook')
    ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]); // ✅ AGREGADO (Stripe)

/* =========================================================================
 * RUTAS PROTEGIDAS (AUTH)
 * ========================================================================= */
Route::middleware(['auth'])->group(function () {

    /* ---------- Dashboard ---------- */
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    /* ---------- Billing / Pagos ---------- */
    Route::get('/billing/alert', [BillingController::class, 'alert'])->name('billing.alert');

    // ✅ CLIP (PRESERVADO - NO SE ELIMINA)
    Route::post('/billing/checkout', [BillingController::class, 'createCheckout'])->name('billing.clip.checkout');

    // ✅ STRIPE (AGREGADO - NO ROMPE LO ANTERIOR)
    Route::post('/billing/stripe/checkout', [BillingController::class, 'createStripeCheckout'])->name('billing.stripe.checkout');
    Route::get('/billing/stripe/success', [BillingController::class, 'stripeSuccess'])->name('billing.stripe.success');
    Route::get('/billing/stripe/cancel', [BillingController::class, 'stripeCancel'])->name('billing.stripe.cancel');

    Route::post('/suscripciones', [SuscripcionController::class, 'store'])->name('suscripciones.store');
    Route::post('/suscripciones/{suscripcion}/renew', [SuscripcionController::class, 'renew'])->name('suscripciones.renew');

    /* ---------- Perfil ---------- */
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    /* =======================================================================
     * SECCIÓN DE REPORTES (CORREGIDA Y UNIFICADA)
     * Soluciona el error: Route [reportes.proveedores] not defined.
     * ======================================================================= */
    Route::prefix('reportes')->name('reportes.')->group(function () {
        // FN.16 Rentabilidad
        Route::get('/rentabilidad', [ReporteController::class, 'rentabilidad'])->name('rentabilidad');
        // FN.17 Suscripciones (El controlador valida si es SA)
        Route::get('/suscripciones', [ReporteController::class, 'suscripciones'])->name('suscripciones');
        // FN.18 Clientes
        Route::get('/clientes', [ReporteController::class, 'clientes'])->name('clientes');
        // FN.19 Inventario
        Route::get('/inventario', [ReporteController::class, 'movimientoInventario'])->name('inventario');
        // FN.20 Proveedores
        Route::get('/proveedores', [ReporteController::class, 'proveedores'])->name('proveedores');
        Route::get('/', [ReporteController::class, 'index'])->name('index');
    });

    /* ---------- Utilidades JSON (Productos) ---------- */
    Route::get('/productos/{producto}/proveedores-json', [CompraController::class, 'proveedoresProducto'])
        ->name('productos.proveedores_json');
    Route::get('/productos/{producto}/costo-para/{proveedor}', [CompraController::class, 'costoProductoProveedor'])
        ->name('productos.costo_para_proveedor');

    /* ---------- Ventas (Unificado) ---------- */
    Route::resource('ventas', VentaController::class)->parameters(['ventas' => 'venta']);
    Route::post('ventas/{venta}/convertir', [VentaController::class, 'convertirPrefactura'])->name('ventas.convertirPrefactura');
    Route::get('ventas/{venta}/pdf', [VentaController::class, 'pdf'])->name('ventas.pdf');

    // ✅ SOLO AÑADIMOS ESTO (CFDI DEMO: XML+PDF+EMAIL, sin PAC)
    Route::post('ventas/{venta}/cfdi-demo/enviar', [VentaController::class, 'enviarCfdiDemo'])
        ->name('ventas.cfdiDemo.enviar');

    /* =========================================================================
     * ✅ GESTIÓN DE USUARIOS (NO CORROMPE: SOLO SE MUEVE AQUÍ)
     * - Acceso: superadmin | administrador_empresa | gerente
     * - Requiere suscripción activa
     * - El controlador decide qué puede ver/crear/editar cada rol
     * ========================================================================= */
    Route::middleware(['suscripcion.activa', 'role:superadmin|administrador_empresa|gerente'])->group(function () {
        Route::resource('users', UserController::class)->except(['show']); // (show no lo usas; si lo quieres, quita el except)
    });

    /* =========================================================================
     * ZONA SUPERADMIN
     * ========================================================================= */
    Route::middleware(['role:superadmin'])->group(function () {

        // ✅ OJO: QUITAMOS users de aquí (ya está arriba en su grupo correcto)

        // Backups (PRESERVADO)
        Route::get('/backups', [BackupController::class, 'index'])->name('backups.index');
        Route::post('/backups/create', [BackupController::class, 'create'])->name('backups.create');
        Route::get('/backups/download', [BackupController::class, 'download'])->name('backups.download');
        Route::delete('/backups/delete', [BackupController::class, 'delete'])->name('backups.delete');
        Route::post('/backups/restore', [BackupController::class, 'restore'])->name('backups.restore');

        // Compras (SA)
        Route::resource('compras', CompraController::class);
        Route::post('/compras/{id}/recibir', [CompraController::class, 'recibir'])->name('compras.recibir');

        // Calendario (SA)
        Route::get('/calendar', [CalendarEventController::class, 'index'])->name('calendar.index');
        Route::get('/calendar/events', [CalendarEventController::class, 'events'])->name('calendar.events');
        Route::post('/calendar/events', [CalendarEventController::class, 'store'])->name('calendar.events.store');
        Route::put('/calendar/events/{event}', [CalendarEventController::class, 'update'])->name('calendar.events.update');
        Route::delete('/calendar/events/{event}', [CalendarEventController::class, 'destroy'])->name('calendar.events.destroy');

        // Catálogos
        Route::resource('categorias', CategoriaController::class)->parameters(['categorias' => 'categoria']);
        Route::resource('productos', ProductoController::class)->parameters(['productos' => 'producto']);
        Route::resource('proveedores', ProveedorController::class)->parameters(['proveedores' => 'proveedore']);

        // Pivotes
        Route::post('productos/{producto}/proveedores', [ProductoProveedorController::class, 'store'])->name('productos.proveedores.store');
        Route::put('productos/{producto}/proveedores/{proveedor}', [ProductoProveedorController::class, 'update'])->name('productos.proveedores.update');
        Route::delete('productos/{producto}/proveedores/{proveedor}', [ProductoProveedorController::class, 'destroy'])->name('productos.proveedores.destroy');

        // Gestión SaaS
        Route::resource('admin-empresas', AdminEmpresaController::class)->parameters(['admin-empresas' => 'admin_empresa']);
        Route::resource('empresas', EmpresaController::class);
        Route::resource('suscripciones', SuscripcionController::class)->parameters(['suscripciones' => 'suscripcion']);
        Route::resource('gerentes', GerenteController::class)->parameters(['gerentes' => 'gerente']);
        Route::resource('vendedores', VendedorController::class)->parameters(['vendedores' => 'vendedor']);

        // Chat (SA)
        Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');
        Route::get('/chat/conversations-json', [ChatController::class, 'listConversations'])->name('chat.conversations.json');
        Route::post('/chat/conversations', [ChatController::class, 'storeConversation'])->name('chat.conversations.store');
        Route::get('/chat/conversations/{conversation}/messages', [ChatController::class, 'messages'])->name('chat.messages.index');
        Route::post('/chat/conversations/{conversation}/messages', [ChatController::class, 'sendMessage'])->name('chat.messages.store');
    });

    /* =========================================================================
     * ZONA APP NORMAL (CON SUSCRIPCIÓN ACTIVA)
     * ========================================================================= */
    Route::middleware(['suscripcion.activa'])->group(function () {

        // Chat (App)
        Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');
        Route::get('/chat/conversations-json', [ChatController::class, 'listConversations'])->name('chat.conversations.json');
        Route::post('/chat/conversations', [ChatController::class, 'storeConversation'])->name('chat.conversations.store');
        Route::get('/chat/conversations/{conversation}/messages', [ChatController::class, 'messages'])->name('chat.messages.index');
        Route::post('/chat/conversations/{conversation}/messages', [ChatController::class, 'sendMessage'])->name('chat.messages.store');

        // Calendario (App)
        Route::get('/calendar', [CalendarEventController::class, 'index'])->name('calendar.index');
        Route::get('/calendar/events', [CalendarEventController::class, 'events'])->name('calendar.events');
        Route::post('/calendar/events', [CalendarEventController::class, 'store'])->name('calendar.events.store');
        Route::put('/calendar/events/{event}', [CalendarEventController::class, 'update'])->name('calendar.events.update');
        Route::delete('/calendar/events/{event}', [CalendarEventController::class, 'destroy'])->name('calendar.events.destroy');

        // Compras
        Route::resource('compras', CompraController::class);
        Route::post('/compras/{id}/recibir', [CompraController::class, 'recibir'])->name('compras.recibir');

        // Catálogos Operativos
        Route::resource('empresas', EmpresaController::class)->names('empresas');
        Route::resource('categorias', CategoriaController::class)->parameters(['categorias' => 'categoria']);
        Route::resource('productos', ProductoController::class)->parameters(['productos' => 'producto']);
        Route::resource('proveedores', ProveedorController::class)->parameters(['proveedores' => 'proveedore']);

        // Pivotes
        Route::post('productos/{producto}/proveedores', [ProductoProveedorController::class, 'store'])->name('productos.proveedores.store');
        Route::put('productos/{producto}/proveedores/{proveedor}', [ProductoProveedorController::class, 'update'])->name('productos.proveedores.update');
        Route::delete('productos/{producto}/proveedores/{proveedor}', [ProductoProveedorController::class, 'destroy'])->name('productos.proveedores.destroy');

        // Recursos Humanos / Clientes
        Route::resource('gerentes', GerenteController::class)->parameters(['gerentes' => 'gerente']);
        Route::resource('vendedores', VendedorController::class)->parameters(['vendedores' => 'vendedor']);
        Route::resource('clientes', ClienteController::class);
    });
});
