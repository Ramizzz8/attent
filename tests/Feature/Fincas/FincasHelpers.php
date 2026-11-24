<?php

use App\Models\Edificio;
use App\Models\Empresa;
use App\Models\Proveedor;
use App\Models\Zona;

/** Administrador de fincas "fincas-levante" con dos edificios y sus zonas. */
function montarFincas($test): void
{
    $test->empresa = Empresa::factory()->fincas()->create(['slug' => 'fincas-levante']);
    $test->edificio = Edificio::create(['empresa_id' => $test->empresa->id, 'nombre' => 'Residencial Mar', 'direccion' => 'Av. del Mar 1', 'slug' => 'residencial-mar']);
    $test->otroEdificio = Edificio::create(['empresa_id' => $test->empresa->id, 'nombre' => 'Torre Sol', 'direccion' => 'C/ Sol 2', 'slug' => 'torre-sol']);
    $test->ascensor = Zona::create(['edificio_id' => $test->edificio->id, 'nombre' => 'Ascensor', 'prioridad_alta' => true]);
    $test->portal = Zona::create(['edificio_id' => $test->edificio->id, 'nombre' => 'Portal', 'prioridad_alta' => false]);
    $test->garajeSol = Zona::create(['edificio_id' => $test->otroEdificio->id, 'nombre' => 'Garaje', 'prioridad_alta' => false]);
    $test->fontanero = Proveedor::create(['empresa_id' => $test->empresa->id, 'nombre' => 'Fontanería Paco', 'oficio' => 'Fontanero', 'email' => 'paco@fontaneria.test']);
    $test->fontanero->edificios()->attach($test->edificio);
}

function altaFincas(array $extra = []): array
{
    return [
        'nombre' => 'Vecina', 'email' => 'vecina@x.es', 'asunto' => 'Portal sucio', 'descripcion' => 'Hay basura',
        'categoria' => 'limpieza', 'edificio_id' => test()->edificio->id, 'zona_id' => test()->portal->id,
        'datos' => ['vivienda' => '2.º A'], ...$extra,
    ];
}
