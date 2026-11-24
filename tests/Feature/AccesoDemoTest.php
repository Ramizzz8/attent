<?php

use App\Models\Empresa;
use App\Models\User;
use Database\Seeders\DemoSeeder;

beforeEach(fn () => $this->seed(DemoSeeder::class));

it('en la demo entra como el administrador de la empresa con un clic', function () {
    config(['attent.demo' => true]);

    $this->post('/demo/entrar/talleres-ruiz')->assertRedirect('/panel');

    $this->assertAuthenticatedAs(User::where('email', 'admin@talleres-ruiz.test')->sole());
});

it('cambia de empresa aunque ya haya una sesión abierta', function () {
    config(['attent.demo' => true]);
    $this->actingAs(User::where('email', 'admin@talleres-ruiz.test')->sole());

    $this->post('/demo/entrar/soporte-tic')->assertRedirect('/panel');

    $this->assertAuthenticatedAs(User::where('email', 'admin@soporte-tic.test')->sole());
});

it('fuera de la demo no existe y nadie inicia sesión', function () {
    config(['attent.demo' => false]);

    $this->post('/demo/entrar/talleres-ruiz')->assertNotFound();
    $this->assertGuest();
});

it('una empresa inexistente o sin cuenta demo da 404', function () {
    config(['attent.demo' => true]);
    Empresa::factory()->create(['slug' => 'real-sin-demo']);

    $this->post('/demo/entrar/no-existe')->assertNotFound();
    $this->post('/demo/entrar/real-sin-demo')->assertNotFound();
    $this->assertGuest();
});
