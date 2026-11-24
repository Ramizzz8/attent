<?php

use App\Models\Empresa;
use App\Models\Ticket;
use App\Models\User;
use Database\Seeders\DemoSeeder;

it('los datos demo cubren todos los estados de los tres sectores', function () {
    $this->seed(DemoSeeder::class);

    expect(Empresa::pluck('slug')->sort()->values()->all())->toBe(['fincas-levante', 'soporte-tic', 'talleres-ruiz']);

    Empresa::all()->each(function (Empresa $e) {
        $usados = Ticket::withoutGlobalScopes()->where('empresa_id', $e->id)->distinct()->pluck('estado')->all();
        $faltan = array_diff(array_keys($e->motor()->estados()), $usados);

        expect($faltan)->toBe([], "A {$e->slug} le faltan estados: ".implode(', ', $faltan));
        expect(User::where('email', "admin@{$e->slug}.test")->sole()->esAdmin())->toBeTrue();
        expect(Ticket::withoutGlobalScopes()->where('empresa_id', $e->id)->count())->toBeGreaterThanOrEqual(12);
    });
});

it('hay incidencias con plazo vencido, mensajes, presupuestos y afectados', function () {
    $this->seed(DemoSeeder::class);

    $tickets = Ticket::withoutGlobalScopes()->get();
    expect($tickets->filter(fn (Ticket $t) => $t->estadoSla() === 'vencido'))->not->toBeEmpty()
        ->and(App\Models\Mensaje::where('interno', true)->exists())->toBeTrue()
        ->and(App\Models\Presupuesto::count())->toBeGreaterThanOrEqual(4)
        ->and(Illuminate\Support\Facades\DB::table('ticket_afectados')->count())->toBeGreaterThanOrEqual(3);
});

it('la demo se carga aunque el servidor de tiempo real no esté disponible', function () {
    config([
        'broadcasting.default' => 'reverb',
        'broadcasting.connections.reverb.key' => 'k',
        'broadcasting.connections.reverb.secret' => 's',
        'broadcasting.connections.reverb.app_id' => '1',
        'broadcasting.connections.reverb.options.host' => '127.0.0.1',
        'broadcasting.connections.reverb.options.port' => 1,
        'broadcasting.connections.reverb.options.scheme' => 'http',
    ]);

    $this->seed(DemoSeeder::class);

    expect(Empresa::count())->toBe(3);
});

it('db:seed (DatabaseSeeder) carga la demo con los eventos de modelo activos', function () {
    $this->seed();

    expect(Empresa::count())->toBe(3)
        ->and(App\Models\Cliente::withoutGlobalScopes()->whereNull('empresa_id')->count())->toBe(0)
        ->and(Illuminate\Support\Carbon::hasTestNow())->toBeFalse();
});
