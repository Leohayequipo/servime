<?php

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

Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    // Help
    Route::get('/help', [HelpController::class, 'index'])->name('help.index');
    
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
        
        // Gestión de Comercios
        Route::get('/comercios', [App\Http\Controllers\AdminController::class, 'comercios'])->name('comercios.index');
        Route::get('/comercios/create', [App\Http\Controllers\AdminController::class, 'createComercio'])->name('comercios.create');
        Route::post('/comercios', [App\Http\Controllers\AdminController::class, 'storeComercio'])->name('comercios.store');
        Route::get('/comercios/{comercio}', [App\Http\Controllers\AdminController::class, 'showComercio'])->name('comercios.show');
        Route::get('/comercios/{comercio}/edit', [App\Http\Controllers\AdminController::class, 'editComercio'])->name('comercios.edit');
        Route::put('/comercios/{comercio}', [App\Http\Controllers\AdminController::class, 'updateComercio'])->name('comercios.update');
        
        // Gestión de Menús
        Route::get('/menus', [App\Http\Controllers\AdminController::class, 'menus'])->name('menus.index');
        Route::get('/menus/create', [App\Http\Controllers\AdminController::class, 'createMenu'])->name('menus.create');
        Route::post('/menus', [App\Http\Controllers\AdminController::class, 'storeMenu'])->name('menus.store');
        Route::get('/menus/{menu}', [App\Http\Controllers\AdminController::class, 'showMenu'])->name('menus.show');
        Route::get('/menus/{menu}/edit', [App\Http\Controllers\AdminController::class, 'editMenu'])->name('menus.edit');
        Route::put('/menus/{menu}', [App\Http\Controllers\AdminController::class, 'updateMenu'])->name('menus.update');
    });
    
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
    Route::post('/comercios/{comercio}/menus/{menu}/activar', [App\Http\Controllers\MenuController::class, 'activar'])->name('menus.activar');
    
    // Rutas de personalización (ahora integradas en editar)
    Route::get('/comercios/{comercio}/preview', [App\Http\Controllers\ComercioController::class, 'preview'])->name('comercios.preview');
    Route::post('/comercios/{comercio}/generate-questions', [App\Http\Controllers\ComercioController::class, 'generateQuestions'])->name('comercios.generate.questions');
    Route::post('/comercios/{comercio}/improve-answer', [App\Http\Controllers\ComercioController::class, 'improveAnswer'])->name('comercios.improve.answer');
});

// Rutas públicas (sin autenticación)
Route::get('/menu/{comercio}', [App\Http\Controllers\PublicMenuController::class, 'show'])->name('menu.public');
Route::post('/api/chat/{comercio}', [App\Http\Controllers\ChatbotController::class, 'chat'])->name('chat.api');

    // Rutas de Categorías de Preguntas
    Route::resource('question-categories', QuestionCategoryController::class);
    
    // Rutas de Analytics (requieren autenticación)
    Route::middleware(['auth'])->group(function () {
        Route::prefix('analytics')->name('analytics.')->group(function () {
            Route::get('/dashboard', [App\Http\Controllers\AnalyticsController::class, 'dashboard'])->name('dashboard');
            Route::get('/comercio/{comercio}', [App\Http\Controllers\AnalyticsController::class, 'comercio'])->name('comercio');
            Route::get('/export/{comercio}', [App\Http\Controllers\AnalyticsController::class, 'exportConversations'])->name('export');
        });
    });

require __DIR__.'/auth.php';
