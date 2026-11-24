<?php

use App\Http\Controllers\Panel\AsignacionController;
use App\Http\Controllers\Panel\BandejaController;
use App\Http\Controllers\Panel\EstadoController;
use App\Http\Controllers\Panel\MensajeController;
use App\Http\Controllers\Panel\PresupuestoController;
use App\Http\Controllers\Panel\TicketController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified', 'empresa.panel'])->group(function () {
    Route::get('panel', [BandejaController::class, 'index'])->name('dashboard');

    Route::prefix('panel')->name('panel.')->group(function () {
        Route::get('tickets/{numero}', [TicketController::class, 'show'])->whereNumber('numero')->name('tickets.show');
        Route::post('tickets/{numero}/estado', [EstadoController::class, 'store'])->whereNumber('numero')->name('tickets.estado');
        Route::post('tickets/{numero}/asignar', [AsignacionController::class, 'store'])->whereNumber('numero')->name('tickets.asignar');
        Route::post('tickets/{numero}/mensajes', [MensajeController::class, 'store'])->whereNumber('numero')->name('mensajes.store');
        Route::post('tickets/{numero}/presupuestos', [PresupuestoController::class, 'store'])->whereNumber('numero')->name('presupuestos.store');
        Route::get('adjuntos/{adjunto}', [MensajeController::class, 'adjunto'])->whereNumber('adjunto')->name('adjuntos.show');
    });
});

require __DIR__.'/settings.php';
