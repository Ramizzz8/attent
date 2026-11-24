<?php

use App\Http\Controllers\Panel\AsignacionController;
use App\Http\Controllers\Panel\BandejaController;
use App\Http\Controllers\Panel\EstadoController;
use App\Http\Controllers\Panel\FincasController;
use App\Http\Controllers\Panel\MensajeController;
use App\Http\Controllers\Panel\PresupuestoController;
use App\Http\Controllers\Panel\TicketController;
use App\Http\Controllers\Proveedor\ProveedorTicketController;
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
        Route::post('tickets/{numero}/proveedor', [FincasController::class, 'asignar'])->whereNumber('numero')->name('tickets.proveedor');

        Route::middleware('admin')->group(function () {
            Route::get('edificios', [FincasController::class, 'index'])->name('edificios.index');
            Route::post('edificios', [FincasController::class, 'storeEdificio'])->name('edificios.store');
            Route::post('proveedores', [FincasController::class, 'storeProveedor'])->name('proveedores.store');
        });

        Route::get('adjuntos/{adjunto}', [MensajeController::class, 'adjunto'])->whereNumber('adjunto')->name('adjuntos.show');
    });
});

// Proveedores externos de fincas: acceden con enlaces firmados, sin cuenta.
Route::prefix('proveedor/{proveedor}/tickets/{ticket}')
    ->whereNumber(['proveedor', 'ticket'])
    ->middleware(['signed', 'throttle:30,1'])
    ->name('proveedor.')
    ->group(function () {
        Route::get('/', [ProveedorTicketController::class, 'show'])->name('ticket');
        Route::post('visita', [ProveedorTicketController::class, 'visita'])->name('visita');
        Route::post('resolver', [ProveedorTicketController::class, 'resolver'])->name('resolver');
    });

require __DIR__.'/settings.php';
