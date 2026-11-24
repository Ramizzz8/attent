<?php

use App\Models\Equipo;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(fn () => $this->seed(DemoSeeder::class));

it('en la demo el panel del taller trae tres tareas guiadas que apuntan a incidencias reales', function () {
    config(['attent.demo' => true]);

    $this->actingAs(User::where('email', 'admin@talleres-ruiz.test')->sole())->get('/panel')->assertInertia(fn (Assert $p) => $p
        ->has('guiaDemo', 3)
        ->where('guiaDemo.0.enlace', '/panel/tickets/1')
        ->where('guiaDemo.1.texto', 'Envía el presupuesto de la incidencia #3')
        ->where('guiaDemo.1.enlace', '/panel/tickets/3'));
});

it('en soporte la tercera tarea lleva a la ficha real del proyector PRY-003', function () {
    config(['attent.demo' => true]);
    $id = Equipo::withoutGlobalScopes()->where('codigo', 'PRY-003')->value('id');

    $this->actingAs(User::where('email', 'admin@soporte-tic.test')->sole())->get('/panel')->assertInertia(fn (Assert $p) => $p
        ->has('guiaDemo', 3)
        ->where('guiaDemo.2.enlace', "/panel/equipos/{$id}"));
});

it('fuera de la demo no hay guía', function () {
    config(['attent.demo' => false]);

    $this->actingAs(User::where('email', 'admin@talleres-ruiz.test')->sole())->get('/panel')->assertInertia(fn (Assert $p) => $p->where('guiaDemo', null));
});
