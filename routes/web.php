<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ComercioController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect('/dashboard');
    }
    return redirect('/login');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/comercios/create', [ComercioController::class, 'create'])->name('comercios.create');
    Route::post('/comercios', [ComercioController::class, 'store'])->name('comercios.store');
    Route::get('/comercios', [ComercioController::class, 'index'])->name('comercios.index');
    Route::get('/comercios/{comercio}/edit', [ComercioController::class, 'edit'])->name('comercios.edit');
    Route::put('/comercios/{comercio}', [ComercioController::class, 'update'])->name('comercios.update');
    Route::delete('/comercios/{comercio}', [ComercioController::class, 'destroy'])->name('comercios.destroy');
});
require __DIR__.'/auth.php';
