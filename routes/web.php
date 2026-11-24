<?php

use App\Http\Controllers\Panel\AdminController;
use App\Http\Controllers\Panel\AsignacionController;
use App\Http\Controllers\Panel\BandejaController;
use App\Http\Controllers\Panel\EstadisticasController;
use App\Http\Controllers\Panel\EstadoController;
use App\Http\Controllers\Panel\FincasController;
use App\Http\Controllers\Panel\MensajeController;
use App\Http\Controllers\Panel\PresupuestoController;
use App\Http\Controllers\Panel\SoporteItController;
use App\Http\Controllers\Panel\TicketController;
use App\Http\Controllers\Proveedor\ProveedorTicketController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => Inertia\Inertia::render('welcome', [
    // En la demo pública se enseñan las empresas de ejemplo; en una instalación real, no.
    'portales' => config('attent.demo')
        ? App\Models\Empresa::orderBy('id')->get()->map(fn ($e) => [
            'nombre' => $e->nombre, 'sector' => $e->sector->value, 'slug' => $e->slug, 'color' => $e->color_primario,
        ])
        : [],
]))->name('home');

Route::middleware(['auth', 'verified', 'empresa.panel'])->group(function () {
    Route::get('panel', [BandejaController::class, 'index'])->name('dashboard');

    Route::prefix('panel')->name('panel.')->group(function () {
        Route::get('tickets/{numero}', [TicketController::class, 'show'])->whereNumber('numero')->name('tickets.show');
        Route::post('tickets/{numero}/estado', [EstadoController::class, 'store'])->whereNumber('numero')->name('tickets.estado');
        Route::post('tickets/{numero}/asignar', [AsignacionController::class, 'store'])->whereNumber('numero')->name('tickets.asignar');
        Route::post('tickets/{numero}/mensajes', [MensajeController::class, 'store'])->whereNumber('numero')->name('mensajes.store');
        Route::get('tickets/{numero}/presupuestos/{version}/pdf', [PresupuestoController::class, 'pdf'])->whereNumber(['numero', 'version'])->name('presupuestos.pdf');
        Route::post('tickets/{numero}/presupuestos', [PresupuestoController::class, 'store'])->whereNumber('numero')->name('presupuestos.store');
        Route::post('tickets/{numero}/proveedor', [FincasController::class, 'asignar'])->whereNumber('numero')->name('tickets.proveedor');

        Route::post('tickets/{numero}/triaje', [SoporteItController::class, 'triaje'])->whereNumber('numero')->name('tickets.triaje');
        Route::get('equipos', [SoporteItController::class, 'index'])->name('equipos.index');
        Route::get('equipos/{equipo}', [SoporteItController::class, 'show'])->whereNumber('equipo')->name('equipos.show');

        Route::middleware('admin')->group(function () {
            Route::get('empleados', [AdminController::class, 'empleados'])->name('empleados.index');
            Route::post('empleados', [AdminController::class, 'crearEmpleado'])->name('empleados.store');
            Route::patch('empleados/{empleado}', [AdminController::class, 'cambiarRol'])->whereNumber('empleado')->name('empleados.update');
            Route::delete('empleados/{empleado}', [AdminController::class, 'eliminarEmpleado'])->whereNumber('empleado')->name('empleados.destroy');
            Route::get('estadisticas', [EstadisticasController::class, 'index'])->name('estadisticas');
            Route::get('ajustes', [AdminController::class, 'ajustes'])->name('ajustes.edit');
            Route::post('ajustes', [AdminController::class, 'guardarAjustes'])->name('ajustes.update');
            Route::post('equipos', [SoporteItController::class, 'store'])->name('equipos.store');
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
