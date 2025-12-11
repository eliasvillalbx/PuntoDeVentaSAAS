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
use App\Http\Controllers\CalendarEventController;

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
 * Esta lógica NO genera bucles porque /dashboard no redirige de vuelta a /.
 */
Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    return view('welcome');
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
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

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
     * RUTAS COMPARTIDAS (superadmin + usuarios con suscripción)
     * ======================================================================= */

    /**
     * Utilidades de compras para front (consulta de proveedores/costos por producto)
     * Disponibles para cualquier autenticado (no mutan estado).
     */
    Route::get('/productos/{producto}/proveedores-json', [CompraController::class, 'proveedoresProducto'])
        ->name('productos.proveedores_json');
    Route::get('/productos/{producto}/costo-para/{proveedor}', [CompraController::class, 'costoProductoProveedor'])
        ->name('productos.costo_para_proveedor');

    /**
     * ==================== VENTAS (UNIFICADO) ====================
     * Evitamos duplicar estas rutas en subgrupos para no romper nombres/URIs.
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


             Route::get('/calendar', [CalendarEventController::class, 'index'])
        ->name('calendar.index');

    Route::get('/calendar/events', [CalendarEventController::class, 'events'])
        ->name('calendar.events');

    Route::post('/calendar/events', [CalendarEventController::class, 'store'])
        ->name('calendar.events.store');

    Route::put('/calendar/events/{event}', [CalendarEventController::class, 'update'])
        ->name('calendar.events.update');

    Route::delete('/calendar/events/{event}', [CalendarEventController::class, 'destroy'])
        ->name('calendar.events.destroy');
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

        Route::resource('gerentes', GerenteController::class)
            ->parameters(['gerentes' => 'gerente']);

        Route::resource('vendedores', VendedorController::class)
            ->parameters(['vendedores' => 'vendedor']);


        Route::get('/chat', [ChatController::class, 'index'])
            ->name('chat.index');

        // Listado de conversaciones + total de no leídos (para widget / vista)
        Route::get('/chat/conversations-json', [ChatController::class, 'listConversations'])
            ->name('chat.conversations.json');

        // Crear conversación (grupo o directa)
        Route::post('/chat/conversations', [ChatController::class, 'storeConversation'])
            ->name('chat.conversations.store');

        // Mensajes de una conversación (JSON)
        Route::get('/chat/conversations/{conversation}/messages', [ChatController::class, 'messages'])
            ->name('chat.messages.index');

        // Enviar mensaje a una conversación (JSON + sockets)
        Route::post('/chat/conversations/{conversation}/messages', [ChatController::class, 'sendMessage'])
            ->name('chat.messages.store');    
    });

    /* =========================================================================
     *   ZONA APP NORMAL (CON SUSCRIPCIÓN ACTIVA)
     *   - Módulos operativos para administradores de empresa, gerentes, vendedores
     * ========================================================================= */
    Route::middleware(['suscripcion.activa'])->group(function () {

        /* ==================== CHAT EMPRESARIAL ==================== */

        // Vista principal tipo Messenger
        Route::get('/chat', [ChatController::class, 'index'])
            ->name('chat.index');


             Route::get('/calendar', [CalendarEventController::class, 'index'])
        ->name('calendar.index');

    Route::get('/calendar/events', [CalendarEventController::class, 'events'])
        ->name('calendar.events');

    Route::post('/calendar/events', [CalendarEventController::class, 'store'])
        ->name('calendar.events.store');

    Route::put('/calendar/events/{event}', [CalendarEventController::class, 'update'])
        ->name('calendar.events.update');

    Route::delete('/calendar/events/{event}', [CalendarEventController::class, 'destroy'])
        ->name('calendar.events.destroy');
        // Listado de conversaciones + total de no leídos (para widget / vista)
        Route::get('/chat/conversations-json', [ChatController::class, 'listConversations'])
            ->name('chat.conversations.json');

        // Crear conversación (grupo o directa)
        Route::post('/chat/conversations', [ChatController::class, 'storeConversation'])
            ->name('chat.conversations.store');

        // Mensajes de una conversación (JSON)
        Route::get('/chat/conversations/{conversation}/messages', [ChatController::class, 'messages'])
            ->name('chat.messages.index');

        // Enviar mensaje a una conversación (JSON + sockets)
        Route::post('/chat/conversations/{conversation}/messages', [ChatController::class, 'sendMessage'])
            ->name('chat.messages.store');

        /* ==================== COMPRAS (operativas) ==================== */
        Route::resource('compras', CompraController::class);
        Route::post('/compras/{id}/recibir', [CompraController::class, 'recibir'])
            ->name('compras.recibir');

        // Empresas (si decides permitir administración a no-SA)
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

/**
 * Webhook extra de Clip.
 * OJO: comparte el mismo name('clip.webhook') que el otro endpoint.
 * Puedes dejarlo si realmente lo usas con distinta URL.
 */
Route::post('/webhooks/clip-checkout', [ClipWebhookController::class, 'handle'])
    ->name('clip.webhook');
