<?php

use App\Http\Controllers\Portal\AccesoController;
use App\Http\Controllers\Portal\AfectadoController;
use App\Http\Controllers\Portal\MensajePortalController;
use App\Http\Controllers\Portal\PortalController;
use App\Http\Controllers\Portal\PresupuestoPortalController;
use App\Http\Controllers\Portal\TicketPortalController;
use Illuminate\Support\Facades\Route;

/*
| Portal público de cada empresa: /{slug}. Se registra el último y el slug
| excluye las rutas reservadas de la aplicación.
*/

Route::prefix('{empresa}')
    ->where(['empresa' => '(?!(?:'.implode('|', config('atiende.slugs_reservados')).')$)[a-z0-9][a-z0-9-]*'])
    ->middleware('empresa.portal')
    ->name('portal.')
    ->group(function () {
        Route::get('/', [PortalController::class, 'inicio'])->name('inicio');
        Route::get('nueva', [TicketPortalController::class, 'create'])->name('nueva');
        Route::post('tickets', [TicketPortalController::class, 'store'])->middleware('throttle:10,1')->name('tickets.store');
        Route::get('pendiente', [PortalController::class, 'pendiente'])->name('pendiente');

        // Administrador de fincas: formulario por edificio, incidencias repetidas y afectados.
        Route::get('edificio/{edificio}', [AfectadoController::class, 'edificio'])->name('edificio');
        Route::get('duplicados', [AfectadoController::class, 'duplicados'])->middleware('throttle:60,1')->name('duplicados');
        Route::post('tickets/{numero}/afectado', [AfectadoController::class, 'store'])->whereNumber('numero')->middleware('throttle:10,1')->name('afectado');

        Route::get('acceder', [AccesoController::class, 'create'])->name('acceder');
        Route::post('acceder', [AccesoController::class, 'store'])->middleware('throttle:5,1');
        Route::post('login', [AccesoController::class, 'login'])->middleware('throttle:5,1')->name('login');

        Route::middleware('signed')->group(function () {
            Route::get('confirmar/{ticket}', [AccesoController::class, 'confirmar'])->whereNumber('ticket')->name('confirmar');
            Route::get('entrar/{cliente}', [AccesoController::class, 'entrar'])->whereNumber('cliente')->name('entrar');
            Route::get('afectado/{ticket}/{cliente}', [AfectadoController::class, 'confirmar'])->whereNumber(['ticket', 'cliente'])->name('afectado.confirmar');
            Route::get('e/{cliente}/{ticket}', [AccesoController::class, 'enlace'])->whereNumber(['cliente', 'ticket'])->name('enlace');
        });

        Route::middleware('cliente.portal')->group(function () {
            Route::get('mis-tickets', [TicketPortalController::class, 'index'])->name('tickets.index');
            Route::get('tickets/{numero}', [TicketPortalController::class, 'show'])->whereNumber('numero')->name('tickets.show');
            Route::post('tickets/{numero}/mensajes', [MensajePortalController::class, 'store'])->whereNumber('numero')->middleware('throttle:20,1')->name('mensajes.store');
            Route::post('tickets/{numero}/valoracion', [TicketPortalController::class, 'valorar'])->whereNumber('numero')->name('tickets.valorar');
            Route::post('tickets/{numero}/reabrir', [TicketPortalController::class, 'reabrir'])->whereNumber('numero')->name('tickets.reabrir');
            Route::get('tickets/{numero}/presupuesto/{version}/pdf', [PresupuestoPortalController::class, 'pdf'])->whereNumber(['numero', 'version'])->name('presupuesto.pdf');
            Route::post('tickets/{numero}/presupuesto', [PresupuestoPortalController::class, 'store'])->whereNumber('numero')->name('presupuesto.responder');
            Route::get('adjuntos/{adjunto}', [MensajePortalController::class, 'adjunto'])->whereNumber('adjunto')->name('adjuntos.show');
            Route::get('cuenta', [AccesoController::class, 'cuenta'])->name('cuenta');
            Route::post('cuenta/password', [AccesoController::class, 'password'])->name('cuenta.password');
            Route::post('salir', [AccesoController::class, 'salir'])->name('salir');
        });
    });
