<?php

use Database\Seeders\DemoSeeder;
use Inertia\Testing\AssertableInertia as Assert;

it('en la demo la landing ofrece los tres portales y el acceso de un clic', function () {
    config(['attent.demo' => true]);
    $this->seed(DemoSeeder::class);

    $this->get('/')->assertInertia(fn (Assert $p) => $p
        ->component('welcome')
        ->has('portales', 3)
        ->has('accesosDemo', 3)
        ->where('accesosDemo.0.etiqueta', 'Entrar como taller')
        ->where('accesosDemo.0.slug', 'talleres-ruiz'));
});

it('fuera de la demo la landing no enseña empresas ni accesos', function () {
    config(['attent.demo' => false]);
    $this->seed(DemoSeeder::class);

    $this->get('/')->assertInertia(fn (Assert $p) => $p->has('portales', 0)->has('accesosDemo', 0));
});
