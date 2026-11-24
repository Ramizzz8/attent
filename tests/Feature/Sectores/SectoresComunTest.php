<?php

use App\Enums\Prioridad;
use App\Enums\SectorClave;
use App\Models\Empresa;
use App\Sectores\Sectores;

dataset('sectores', SectorClave::cases());

it('todos los estados referenciados existen', function (SectorClave $clave) {
    $s = Sectores::para($clave);
    $estados = array_keys($s->estados());

    $referenciados = [$s->estadoInicial(), $s->estadoResuelto(), $s->estadoObjetivoSla(),
        ...$s->estadosPausaSla(), ...$s->estadosSoloSistema(), ...$s->estadosTerminados()];
    if ($s->estadoCerrado() !== null) {
        $referenciados[] = $s->estadoCerrado();
    }
    foreach ($s->transiciones() as $desde => $destinos) {
        $referenciados = [...$referenciados, $desde, ...$destinos];
    }

    expect(array_diff($referenciados, $estados))->toBe([]);
})->with('sectores');

it('cada sector tiene categorías', function (SectorClave $clave) {
    expect(Sectores::para($clave)->categorias())->not->toBeEmpty();
})->with('sectores');

it('el registro devuelve el sector pedido', function (SectorClave $clave) {
    expect(Sectores::para($clave)->clave())->toBe($clave);
})->with('sectores');

it('la empresa puede sobrescribir los minutos de SLA', function () {
    $e = Empresa::factory()->fincas()->create(['ajustes' => ['sla_minutos' => ['alta' => 90]]]);

    expect($e->motor()->slaMinutos(Prioridad::Alta, $e))->toBe(90)
        ->and($e->motor()->slaMinutos(Prioridad::Media, $e))->toBe(4320);
});

it('prioridad sube un nivel sin pasar de urgente', function () {
    expect(Prioridad::Baja->subir())->toBe(Prioridad::Media)
        ->and(Prioridad::Alta->subir())->toBe(Prioridad::Urgente)
        ->and(Prioridad::Urgente->subir())->toBe(Prioridad::Urgente);
});

it('cada estado de cada sector tiene descripción', function (SectorClave $clave) {
    $s = Sectores::para($clave);

    expect(array_keys($s->descripciones()))->toEqualCanonicalizing(array_keys($s->estados()));
})->with('sectores');

it('el siguiente paso solo apunta a transiciones permitidas', function (SectorClave $clave) {
    $s = Sectores::para($clave);

    foreach ($s->siguientePaso() as $desde => $hacia) {
        expect($s->transiciones()[$desde] ?? [])->toContain($hacia);
    }
})->with('sectores');
