<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ComercioController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HelpController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect('/dashboard');
    }
    return redirect('/login');
});

// Ruta para servir logos directamente
Route::get('/logos/{filename}', function ($filename) {
    $path = storage_path('app/public/logos/' . $filename);
    
    if (!file_exists($path)) {
        abort(404);
    }
    
    return response()->file($path);
})->name('logos.show');

Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    // Help
    Route::get('/help', [HelpController::class, 'index'])->name('help.index');
    
    // Rutas de Comercios
    Route::get('/comercios/create', [ComercioController::class, 'create'])->name('comercios.create');
    Route::post('/comercios', [ComercioController::class, 'store'])->name('comercios.store');
    Route::get('/comercios', [ComercioController::class, 'index'])->name('comercios.index');
    Route::get('/comercios/{comercio}/edit', [ComercioController::class, 'edit'])->name('comercios.edit');
    Route::put('/comercios/{comercio}', [ComercioController::class, 'update'])->name('comercios.update');
    Route::delete('/comercios/{comercio}', [ComercioController::class, 'destroy'])->name('comercios.destroy');
    
    // Rutas de Menús
    Route::get('/menus', [App\Http\Controllers\MenuController::class, 'all'])->name('menus.all');
    Route::get('/comercios/{comercio}/menus', [App\Http\Controllers\MenuController::class, 'index'])->name('menus.index');
    Route::get('/comercios/{comercio}/menus/create', [App\Http\Controllers\MenuController::class, 'create'])->name('menus.create');
    Route::post('/comercios/{comercio}/menus', [App\Http\Controllers\MenuController::class, 'store'])->name('menus.store');
    Route::get('/comercios/{comercio}/menus/{menu}', [App\Http\Controllers\MenuController::class, 'show'])->name('menus.show');
    Route::get('/comercios/{comercio}/menus/{menu}/edit', [App\Http\Controllers\MenuController::class, 'edit'])->name('menus.edit');
    Route::put('/comercios/{comercio}/menus/{menu}', [App\Http\Controllers\MenuController::class, 'update'])->name('menus.update');
    Route::delete('/comercios/{comercio}/menus/{menu}', [App\Http\Controllers\MenuController::class, 'destroy'])->name('menus.destroy');
    
    // Rutas de personalización
    Route::get('/comercios/{comercio}/customize', [App\Http\Controllers\CustomizationController::class, 'show'])->name('comercios.customize');
    Route::put('/comercios/{comercio}/customize', [App\Http\Controllers\CustomizationController::class, 'update'])->name('comercios.customize.update');
    Route::get('/comercios/{comercio}/preview', [App\Http\Controllers\CustomizationController::class, 'preview'])->name('comercios.preview');
    Route::post('/comercios/{comercio}/reset', [App\Http\Controllers\CustomizationController::class, 'reset'])->name('comercios.reset');
});

// Rutas públicas (sin autenticación)
Route::get('/menu/{comercio}', [App\Http\Controllers\PublicMenuController::class, 'show'])->name('menu.public');
Route::post('/api/chat/{comercio}', [App\Http\Controllers\ChatbotController::class, 'chat'])->name('chat.api');

// Rutas de Analytics (requieren autenticación)
Route::middleware(['auth'])->group(function () {
    Route::prefix('analytics')->name('analytics.')->group(function () {
        Route::get('/dashboard', [App\Http\Controllers\AnalyticsController::class, 'dashboard'])->name('dashboard');
        Route::get('/comercio/{comercio}', [App\Http\Controllers\AnalyticsController::class, 'comercio'])->name('comercio');
        Route::get('/export/{comercio}', [App\Http\Controllers\AnalyticsController::class, 'exportConversations'])->name('export');
    });
});

require __DIR__.'/auth.php';
