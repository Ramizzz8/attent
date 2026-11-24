<?php

use App\Enums\Prioridad;
use App\Enums\SectorClave;
use App\Models\Empresa;
use App\Models\Ticket;
use App\Sectores\Sectores;

beforeEach(fn () => $this->fincas = Sectores::para(SectorClave::Fincas));

it('seguridad empieza en alta y el resto en media', function () {
    $e = Empresa::factory()->fincas()->create();

    expect($this->fincas->prioridadInicial(['categoria' => 'seguridad'], $e))->toBe(Prioridad::Alta)
        ->and($this->fincas->prioridadInicial(['categoria' => 'limpieza'], $e))->toBe(Prioridad::Media);
});

it('descartar exige motivo', function () {
    $t = Ticket::factory()->fincas()->create(['estado' => 'reportada']);

    expect($this->fincas->validarTransicion($t, 'descartada'))->toBe('Indica el motivo para descartar la incidencia.')
        ->and($this->fincas->validarTransicion($t, 'descartada', 'Duplicada'))->toBeNull();
});

it('asignar a proveedor exige proveedor', function () {
    $t = Ticket::factory()->fincas()->create(['estado' => 'en_revision']);

    expect($this->fincas->validarTransicion($t, 'asignada_proveedor'))->toBe('Elige un proveedor antes de asignar.');
});

it('valores de fincas', function () {
    $e = Empresa::factory()->fincas()->create();

    expect($this->fincas->estadoInicial())->toBe('reportada')
        ->and($this->fincas->slaMinutos(Prioridad::Baja, $e))->toBe(10080)
        ->and($this->fincas->slaMinutos(Prioridad::Urgente, $e))->toBe(480)
        ->and($this->fincas->estadosTerminados())->toBe(['resuelta', 'cerrada', 'descartada'])
        ->and($this->fincas->estadoCerrado())->toBe('cerrada');
});

it('reabre en asignada si hay proveedor y en revisión si no', function () {
    $sin = Ticket::factory()->fincas()->create(['estado' => 'resuelta']);

    expect($this->fincas->estadoReapertura($sin))->toBe('en_revision');
});
