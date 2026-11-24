<?php

use App\Enums\Prioridad;
use App\Enums\SectorClave;
use App\Models\Empresa;
use App\Models\Presupuesto;
use App\Models\Ticket;
use App\Sectores\Sectores;

beforeEach(fn () => $this->taller = Sectores::para(SectorClave::Taller));

it('no permite reparar sin presupuesto', function () {
    $t = Ticket::factory()->taller()->create(['estado' => 'aprobado']);

    expect($this->taller->validarTransicion($t, 'en_reparacion'))
        ->toBe('No se puede reparar sin un presupuesto aprobado.');
});

it('permite reparar con el presupuesto vigente aprobado', function () {
    $t = Ticket::factory()->taller()->create(['estado' => 'aprobado']);
    Presupuesto::factory()->for($t)->create(['version' => 1, 'estado' => 'aprobado']);

    expect($this->taller->validarTransicion($t, 'en_reparacion'))->toBeNull();
});

it('no permite reparar si la última versión fue rechazada', function () {
    $t = Ticket::factory()->taller()->create(['estado' => 'rechazado']);
    Presupuesto::factory()->for($t)->create(['version' => 1, 'estado' => 'aprobado']);
    Presupuesto::factory()->for($t)->create(['version' => 2, 'estado' => 'rechazado']);

    expect($this->taller->validarTransicion($t, 'en_reparacion'))->not->toBeNull();
});

it('reabre en reparación con presupuesto aprobado y en diagnóstico si no', function () {
    $con = Ticket::factory()->taller()->create(['estado' => 'entregado']);
    Presupuesto::factory()->for($con)->create(['estado' => 'aprobado']);
    $sin = Ticket::factory()->taller()->create(['estado' => 'entregado']);
    Presupuesto::factory()->for($sin)->create(['estado' => 'rechazado']);

    expect($this->taller->estadoReapertura($con))->toBe('en_reparacion')
        ->and($this->taller->estadoReapertura($sin))->toBe('en_diagnostico');
});

it('valores del taller', function () {
    $e = Empresa::factory()->taller()->create();

    expect($this->taller->estadoInicial())->toBe('recibido')
        ->and($this->taller->prioridadInicial([], $e))->toBe(Prioridad::Media)
        ->and($this->taller->slaMinutos(Prioridad::Urgente, $e))->toBe(2880)
        ->and($this->taller->estadoObjetivoSla())->toBe('presupuesto_enviado')
        ->and($this->taller->estadosPausaSla())->toBe(['presupuesto_enviado', 'esperando_piezas'])
        ->and($this->taller->estadoResuelto())->toBe('entregado')
        ->and($this->taller->estadoCerrado())->toBeNull()
        ->and($this->taller->estadosSoloSistema())->toBe(['presupuesto_enviado', 'aprobado', 'rechazado']);
});
