<?php

// Auto-deploy test

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ComercioController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HelpController;
use App\Http\Controllers\QuestionCategoryController;
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

// Help - Accesible sin autenticación
Route::get('/help', [HelpController::class, 'index'])->name('help.index');

Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    // Rutas de Administración (Solo Super Administradores)
    Route::middleware('super_admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [App\Http\Controllers\AdminController::class, 'dashboard'])->name('dashboard');
        
        // Gestión de Usuarios
        Route::get('/users', [App\Http\Controllers\AdminController::class, 'users'])->name('users.index');
        Route::get('/users/create', [App\Http\Controllers\AdminController::class, 'createUser'])->name('users.create');
        Route::post('/users', [App\Http\Controllers\AdminController::class, 'storeUser'])->name('users.store');
        Route::get('/users/{user}', [App\Http\Controllers\AdminController::class, 'showUser'])->name('users.show');
        Route::get('/users/{user}/edit', [App\Http\Controllers\AdminController::class, 'editUser'])->name('users.edit');
        Route::put('/users/{user}', [App\Http\Controllers\AdminController::class, 'updateUser'])->name('users.update');
        Route::delete('/users/{user}', [App\Http\Controllers\AdminController::class, 'destroyUser'])->name('users.destroy');
        
        // Gestión de Comercios (usando slug)
        Route::get('/comercios', [App\Http\Controllers\AdminController::class, 'comercios'])->name('comercios.index');
        Route::get('/comercios/create', [App\Http\Controllers\AdminController::class, 'createComercio'])->name('comercios.create');
        Route::post('/comercios', [App\Http\Controllers\AdminController::class, 'storeComercio'])->name('comercios.store');
        Route::get('/comercios/{comercio:slug}', [App\Http\Controllers\AdminController::class, 'showComercio'])->name('comercios.show');
        Route::put('/comercios/{comercio:slug}', [App\Http\Controllers\AdminController::class, 'updateComercio'])->name('comercios.update');
        Route::delete('/comercios/{comercio:slug}', [App\Http\Controllers\AdminController::class, 'destroyComercio'])->name('comercios.destroy');
        Route::post('/comercios/{comercio:slug}/generate-questions', [App\Http\Controllers\AdminController::class, 'generateQuestions'])->name('comercios.generate.questions');
        
        // Gestión de Menús
        Route::get('/menus', [App\Http\Controllers\AdminController::class, 'menus'])->name('menus.index');
        Route::get('/menus/create', [App\Http\Controllers\AdminController::class, 'createMenu'])->name('menus.create');
        Route::post('/menus', [App\Http\Controllers\AdminController::class, 'storeMenu'])->name('menus.store');
        Route::get('/menus/{menu}', [App\Http\Controllers\AdminController::class, 'showMenu'])->name('menus.show');
        Route::get('/menus/{menu}/edit', [App\Http\Controllers\AdminController::class, 'editMenu'])->name('menus.edit');
        Route::put('/menus/{menu}', [App\Http\Controllers\AdminController::class, 'updateMenu'])->name('menus.update');
    });
    
    // Rutas de Comercios (usando slug)
    Route::get('/comercios/create', [ComercioController::class, 'create'])->name('comercios.create');
    Route::post('/comercios', [ComercioController::class, 'store'])->name('comercios.store');
    Route::get('/comercios', [ComercioController::class, 'index'])->name('comercios.index');
    Route::get('/comercios/{comercio:slug}/edit', [ComercioController::class, 'edit'])->name('comercios.edit');
    Route::put('/comercios/{comercio:slug}', [ComercioController::class, 'update'])->name('comercios.update');
    Route::delete('/comercios/{comercio:slug}', [ComercioController::class, 'destroy'])->name('comercios.destroy');
    
    // Rutas de Menús (usando slug para comercio)
    Route::get('/menus', [App\Http\Controllers\MenuController::class, 'all'])->name('menus.all');
    Route::get('/comercios/{comercio:slug}/menus', [App\Http\Controllers\MenuController::class, 'index'])->name('menus.index');
    Route::get('/comercios/{comercio:slug}/menus/create', [App\Http\Controllers\MenuController::class, 'create'])->name('menus.create');
    Route::post('/comercios/{comercio:slug}/menus', [App\Http\Controllers\MenuController::class, 'store'])->name('menus.store');
    Route::get('/comercios/{comercio:slug}/menus/{menu}', [App\Http\Controllers\MenuController::class, 'show'])->name('menus.show');
    Route::get('/comercios/{comercio:slug}/menus/{menu}/edit', [App\Http\Controllers\MenuController::class, 'edit'])->name('menus.edit');
    Route::put('/comercios/{comercio:slug}/menus/{menu}', [App\Http\Controllers\MenuController::class, 'update'])->name('menus.update');
    Route::delete('/comercios/{comercio:slug}/menus/{menu}', [App\Http\Controllers\MenuController::class, 'destroy'])->name('menus.destroy');
    Route::post('/comercios/{comercio:slug}/menus/{menu}/activar', [App\Http\Controllers\MenuController::class, 'activar'])->name('menus.activar');
    Route::post('/comercios/{comercio:slug}/menus/{menu}/upload-plato-imagen', [App\Http\Controllers\MenuController::class, 'uploadPlatoImagen'])->name('menus.upload.plato.imagen');
    
    // Rutas de personalización (ahora integradas en editar, usando slug)
    Route::get('/comercios/{comercio:slug}/preview', [App\Http\Controllers\ComercioController::class, 'preview'])->name('comercios.preview');
    Route::post('/comercios/{comercio:slug}/generate-questions', [App\Http\Controllers\ComercioController::class, 'generateQuestions'])->name('comercios.generate.questions');
    Route::post('/comercios/{comercio:slug}/improve-answer', [App\Http\Controllers\ComercioController::class, 'improveAnswer'])->name('comercios.improve.answer');
});

// Rutas públicas (sin autenticación, usando slug)
Route::get('/menu/{comercio:slug}', [App\Http\Controllers\PublicMenuController::class, 'show'])->name('menu.public');
Route::post('/api/chat/{comercio:slug}', [App\Http\Controllers\ChatbotController::class, 'chat'])->name('chat.api');

    // Rutas de Categorías de Preguntas
    Route::resource('question-categories', QuestionCategoryController::class);
    
    // Rutas de Analytics (requieren autenticación, usando slug)
    Route::middleware(['auth'])->group(function () {
        Route::prefix('analytics')->name('analytics.')->group(function () {
            Route::get('/dashboard', [App\Http\Controllers\AnalyticsController::class, 'dashboard'])->name('dashboard');
            Route::get('/comercio/{comercio:slug}', [App\Http\Controllers\AnalyticsController::class, 'comercio'])->name('comercio');
            Route::get('/export/{comercio:slug}', [App\Http\Controllers\AnalyticsController::class, 'exportConversations'])->name('export');
        });
    });

require __DIR__.'/auth.php';
