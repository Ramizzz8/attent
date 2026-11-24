<?php

use App\Models\Empresa;
use Database\Seeders\DemoSeeder;
use Inertia\Testing\AssertableInertia as Assert;

it('la aplicación se llama ATTENT', function () {
    expect(config('app.name'))->toBe('ATTENT');

    $this->get('/')->assertInertia(fn (Assert $p) => $p->where('name', 'ATTENT'));
});

it('la configuración propia está en config/attent.php', function () {
    expect(config('attent.slugs_reservados'))->toContain('panel')
        ->and(config('atiende'))->toBeNull();
});

it('los comandos usan el prefijo attent', function () {
    $this->artisan('attent:preparar')->assertSuccessful();
});

it('la empresa demo de soporte es IES Ribera', function () {
    $this->seed(DemoSeeder::class);

    expect(Empresa::where('slug', 'soporte-tic')->value('nombre'))->toBe('IES Ribera · Soporte TIC');
});
