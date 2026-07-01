<?php

// Auto-deploy test

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ComercioController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect('/dashboard');
    }
    return view('marketing.landing');
});

// Ruta para servir logos directamente
Route::get('/logos/{filename}', function ($filename) {
    if (!preg_match('/^[a-zA-Z0-9._-]+$/', $filename)) {
        abort(404);
    }

    $allowedDir = realpath(storage_path('app/public/logos'));
    $realPath   = realpath($allowedDir . DIRECTORY_SEPARATOR . $filename);

    if (!$realPath || !str_starts_with($realPath, $allowedDir) || !is_file($realPath)) {
        abort(404);
    }

    return response()->file($realPath);
})->name('logos.show');


Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    // Rutas de Administración (Solo Super Administradores)
    Route::middleware('super_admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', function () {
            return redirect()->route('dashboard');
        })->name('dashboard');
        
        // Gestión de Usuarios
        Route::get('/users', [App\Http\Controllers\AdminController::class, 'users'])->name('users.index');
        Route::get('/users/create', [App\Http\Controllers\AdminController::class, 'createUser'])->name('users.create');
        Route::post('/users', [App\Http\Controllers\AdminController::class, 'storeUser'])->name('users.store');
        Route::get('/users/{user}', [App\Http\Controllers\AdminController::class, 'showUser'])->name('users.show');
        Route::get('/users/{user}/edit', [App\Http\Controllers\AdminController::class, 'editUser'])->name('users.edit');
        Route::put('/users/{user}', [App\Http\Controllers\AdminController::class, 'updateUser'])->name('users.update');
        Route::delete('/users/{user}', [App\Http\Controllers\AdminController::class, 'destroyUser'])->name('users.destroy');
        
        // Gestión de Planes y asignación a comercios
        Route::get('/plans', [App\Http\Controllers\PlanController::class, 'index'])->name('plans.index');
        Route::get('/plans/create', [App\Http\Controllers\PlanController::class, 'create'])->name('plans.create');
        Route::post('/plans', [App\Http\Controllers\PlanController::class, 'store'])->name('plans.store');
        Route::get('/plans/{plan}/edit', [App\Http\Controllers\PlanController::class, 'edit'])->name('plans.edit');
        Route::put('/plans/{plan}', [App\Http\Controllers\PlanController::class, 'update'])->name('plans.update');
        Route::delete('/plans/{plan}', [App\Http\Controllers\PlanController::class, 'destroy'])->name('plans.destroy');
        Route::put('/comercios/{comercio:slug}/plan', [App\Http\Controllers\PlanController::class, 'assignToComercio'])->name('comercios.assign-plan');
        Route::post('/comercios/{comercio:slug}/reset-ia', [App\Http\Controllers\PlanController::class, 'resetIaUsage'])->name('comercios.reset-ia');

        // Gestión de Comercios (usando slug)
        Route::get('/comercios', [App\Http\Controllers\AdminController::class, 'comercios'])->name('comercios.index');
        Route::get('/comercios/{comercio:slug}', [App\Http\Controllers\AdminController::class, 'showComercio'])->name('comercios.show');
        Route::put('/comercios/{comercio:slug}', [App\Http\Controllers\AdminController::class, 'updateComercio'])->name('comercios.update');
        Route::delete('/comercios/{comercio:slug}', [App\Http\Controllers\AdminController::class, 'destroyComercio'])->name('comercios.destroy');
        
        // Gestión de Menús
        Route::get('/menus', [App\Http\Controllers\AdminController::class, 'menus'])->name('menus.index');
        Route::get('/menus/deleted', [App\Http\Controllers\AdminController::class, 'menusDeleted'])->name('menus.deleted');
        Route::post('/menus/{menuId}/restore', [App\Http\Controllers\AdminController::class, 'restoreMenu'])->name('menus.restore');
        Route::get('/menus/{menu}', [App\Http\Controllers\AdminController::class, 'showMenu'])->name('menus.show');
        Route::get('/menus/{menu}/edit', [App\Http\Controllers\AdminController::class, 'editMenu'])->name('menus.edit');
        Route::put('/menus/{menu}', [App\Http\Controllers\AdminController::class, 'updateMenu'])->name('menus.update');

        // Chat del landing
        Route::get('/landing-chat', [App\Http\Controllers\AdminController::class, 'landingChat'])->name('landing-chat.index');
    });
    
    // Rutas de Comercios (usando slug)
    Route::get('/comercios/create', [ComercioController::class, 'create'])->name('comercios.create');
    Route::post('/comercios', [ComercioController::class, 'store'])->name('comercios.store');
    Route::get('/comercios', [ComercioController::class, 'index'])->name('comercios.index');
    Route::get('/comercios/{comercio:slug}/edit', [ComercioController::class, 'edit'])->name('comercios.edit');
    Route::put('/comercios/{comercio:slug}', [ComercioController::class, 'update'])->name('comercios.update');
    Route::delete('/comercios/{comercio:slug}', [ComercioController::class, 'destroy'])->name('comercios.destroy');

    // Rutas de Menús (usando slug para comercio)
    Route::get('/comercios/{comercio:slug}/menus', [App\Http\Controllers\MenuController::class, 'index'])->name('menus.index');
    Route::get('/comercios/{comercio:slug}/menus/create', [App\Http\Controllers\MenuController::class, 'create'])->name('menus.create');
    Route::post('/comercios/{comercio:slug}/menus', [App\Http\Controllers\MenuController::class, 'store'])->name('menus.store')->middleware('throttle:5,1');
    Route::get('/comercios/{comercio:slug}/menus/{menu}', [App\Http\Controllers\MenuController::class, 'show'])->name('menus.show');
    Route::get('/comercios/{comercio:slug}/menus/{menu}/edit', [App\Http\Controllers\MenuController::class, 'edit'])->name('menus.edit');
    Route::put('/comercios/{comercio:slug}/menus/{menu}', [App\Http\Controllers\MenuController::class, 'update'])->name('menus.update');
    Route::delete('/comercios/{comercio:slug}/menus/{menu}', [App\Http\Controllers\MenuController::class, 'destroy'])->name('menus.destroy');
    Route::post('/comercios/{comercio:slug}/menus/{menu}/activar', [App\Http\Controllers\MenuController::class, 'activar'])->name('menus.activar');
    Route::post('/comercios/{comercio:slug}/menus/{menu}/upload-plato-imagen', [App\Http\Controllers\MenuController::class, 'uploadPlatoImagen'])->name('menus.upload.plato.imagen');
    Route::delete('/comercios/{comercio:slug}/menus/{menu}/delete-plato-imagen', [App\Http\Controllers\MenuController::class, 'deletePlatoImagen'])->name('menus.delete.plato.imagen');
    Route::post('/comercios/{comercio:slug}/menus/{menu}/update-plato-precio', [App\Http\Controllers\MenuController::class, 'updatePlatoPrecio'])->name('menus.update.plato.precio');
    Route::post('/comercios/{comercio:slug}/menus/{menu}/update-plato-nombre', [App\Http\Controllers\MenuController::class, 'updatePlatoNombre'])->name('menus.update.plato.nombre');
    Route::post('/comercios/{comercio:slug}/menus/{menu}/update-plato-descripcion', [App\Http\Controllers\MenuController::class, 'updatePlatoDescripcion'])->name('menus.update.plato.descripcion');
    Route::post('/comercios/{comercio:slug}/menus/{menu}/update-plato-tags', [App\Http\Controllers\MenuController::class, 'updatePlatoTags'])->name('menus.update.plato.tags');
    Route::post('/comercios/{comercio:slug}/menus/{menu}/reordenar-platos', [App\Http\Controllers\MenuController::class, 'reordenarPlatos'])->name('menus.reordenar.platos');
    Route::post('/comercios/{comercio:slug}/menus/{menu}/upload-promo-imagen', [App\Http\Controllers\MenuController::class, 'uploadPromocionImagen'])->name('menus.upload.promo.imagen')->middleware('plan.feature:promociones');
    Route::get('/comercios/{comercio:slug}/menus/{menu}/export', [App\Http\Controllers\MenuController::class, 'export'])->name('menus.export')->middleware('plan.feature:excel');
    Route::post('/comercios/{comercio:slug}/menus/preview-import', [App\Http\Controllers\MenuController::class, 'previewImport'])->name('menus.preview.import')->middleware('plan.feature:excel');
    Route::post('/comercios/{comercio:slug}/menus/import', [App\Http\Controllers\MenuController::class, 'import'])->name('menus.import')->middleware('plan.feature:excel');
    Route::post('/comercios/{comercio:slug}/menus/update-theme-colors', [App\Http\Controllers\MenuController::class, 'updateThemeColors'])->name('menus.update.theme.colors');
    Route::post('/comercios/{comercio:slug}/menus/update-chatbot-config', [App\Http\Controllers\MenuController::class, 'updateChatbotConfig'])->name('menus.update.chatbot.config');
    Route::delete('/comercios/{comercio:slug}/menus/{menu}/delete-promo-imagen', [App\Http\Controllers\MenuController::class, 'deletePromocionImagen'])->name('menus.delete.promo.imagen')->middleware('plan.feature:promociones');
    Route::delete('/comercios/{comercio:slug}/menus/{menu}/delete-promocion', [App\Http\Controllers\MenuController::class, 'deletePromocion'])->name('menus.delete.promocion')->middleware('plan.feature:promociones');
    Route::post('/comercios/{comercio:slug}/menus/{menu}/save-promociones', [App\Http\Controllers\MenuController::class, 'savePromocionesAjax'])->name('menus.save.promociones')->middleware('plan.feature:promociones');
    
    // Rutas de personalización (ahora integradas en editar, usando slug)
});

// Chat de ventas de la landing (público, con throttle)
Route::post('/api/landing-chat', [App\Http\Controllers\LandingChatController::class, 'chat'])
    ->middleware('throttle:20,1')
    ->name('landing.chat');

// Rutas públicas (sin autenticación, usando slug)
Route::get('/menu/{comercio:slug}', [App\Http\Controllers\PublicMenuController::class, 'choose'])->name('menu.public');
Route::get('/menu/{comercio:slug}/chat', [App\Http\Controllers\PublicMenuController::class, 'chat'])->name('menu.chat');
Route::get('/menu/{comercio:slug}/classic', [App\Http\Controllers\PublicMenuController::class, 'classic'])->name('menu.classic');
Route::post('/api/chat/{comercio:slug}', [App\Http\Controllers\ChatbotController::class, 'chat'])->name('chat.api');

// Tracking de eventos del menú público (sin auth, por sesión)
Route::post('/api/track/{comercio:slug}', [App\Http\Controllers\TrackingController::class, 'store'])
    ->middleware('throttle:120,1')->name('track');

// API ligera para carrito (por sesión, sin auth)
Route::prefix('api/cart')->middleware('throttle:60,1')->group(function () {
    Route::post('/show', [App\Http\Controllers\CartController::class, 'show'])->name('api.cart.show');
    Route::post('/incr', [App\Http\Controllers\CartController::class, 'incr'])->name('api.cart.incr');
    Route::post('/remove', [App\Http\Controllers\CartController::class, 'remove'])->name('api.cart.remove');
    Route::post('/clear', [App\Http\Controllers\CartController::class, 'clear'])->name('api.cart.clear');
});

    // Rutas de Analytics (requieren autenticación, usando slug)
    Route::middleware(['auth'])->group(function () {
        Route::prefix('analytics')->name('analytics.')->group(function () {
            Route::get('/dashboard', [App\Http\Controllers\AnalyticsController::class, 'dashboard'])->name('dashboard');
            Route::get('/comercio/{comercio:slug}', [App\Http\Controllers\AnalyticsController::class, 'comercio'])->name('comercio');
            Route::get('/export/{comercio:slug}', [App\Http\Controllers\AnalyticsController::class, 'exportConversations'])->name('export');
            Route::post('/comercio/{comercio:slug}/reclassify', [App\Http\Controllers\AnalyticsController::class, 'reclassifyIntents'])
                ->name('reclassify')->middleware('plan.feature:intencion_ia');
        });
    });

require __DIR__.'/auth.php';
