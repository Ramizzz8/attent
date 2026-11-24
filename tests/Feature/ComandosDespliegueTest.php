<?php

use App\Models\Empresa;
use App\Models\Ticket;
use Illuminate\Support\Facades\Storage;

it('preparar carga la demo solo si la base de datos está vacía', function () {
    $this->artisan('atiende:preparar')->assertSuccessful();
    expect(Empresa::count())->toBe(3);
    $tickets = Ticket::withoutGlobalScopes()->count();

    $this->artisan('atiende:preparar')->assertSuccessful();
    expect(Empresa::count())->toBe(3)
        ->and(Ticket::withoutGlobalScopes()->count())->toBe($tickets);
});

it('reiniciar-demo deja la demo como al principio y borra los adjuntos subidos', function () {
    Storage::fake('local');
    Storage::fake('public');
    $this->artisan('atiende:preparar');
    Empresa::where('slug', 'talleres-ruiz')->update(['nombre' => 'Cambiado por un visitante']);
    Storage::disk('local')->put('adjuntos/1/1/foto.jpg', 'x');
    Storage::disk('public')->put('logos/1/logo.png', 'x');

    $this->artisan('atiende:reiniciar-demo')->assertSuccessful();

    expect(Empresa::where('slug', 'talleres-ruiz')->value('nombre'))->toBe('Talleres Ruiz');
    Storage::disk('local')->assertMissing('adjuntos/1/1/foto.jpg');
    Storage::disk('public')->assertMissing('logos/1/logo.png');
});

it('reiniciar-demo se niega a ejecutarse fuera del modo demo en producción', function () {
    config(['atiende.demo' => false]);
    app()->detectEnvironment(fn () => 'production');

    $this->artisan('atiende:reiniciar-demo')->assertFailed();
});
