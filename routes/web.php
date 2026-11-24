<?php

use App\Http\Controllers\Panel\MensajeController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified', 'empresa.panel'])->group(function () {
    Route::inertia('panel', 'dashboard')->name('dashboard');

    Route::prefix('panel')->name('panel.')->group(function () {
        Route::post('tickets/{numero}/mensajes', [MensajeController::class, 'store'])->whereNumber('numero')->name('mensajes.store');
        Route::get('adjuntos/{adjunto}', [MensajeController::class, 'adjunto'])->whereNumber('adjunto')->name('adjuntos.show');
    });
});

require __DIR__.'/settings.php';
