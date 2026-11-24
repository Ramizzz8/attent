<?php

use App\Enums\Prioridad;
use App\Enums\SectorClave;
use App\Models\Empresa;
use App\Models\Ticket;
use App\Sectores\Sectores;
use App\SoporteIt\MatrizPrioridad;

beforeEach(fn () => $this->it = Sectores::para(SectorClave::SoporteIt));

it('matriz de prioridad completa', function (string $impacto, string $urgencia, Prioridad $esperada) {
    expect(MatrizPrioridad::calcular($impacto, $urgencia))->toBe($esperada);
})->with([
    ['individual', 'baja', Prioridad::Baja], ['departamento', 'baja', Prioridad::Baja], ['todos', 'baja', Prioridad::Media],
    ['individual', 'media', Prioridad::Baja], ['departamento', 'media', Prioridad::Media], ['todos', 'media', Prioridad::Alta],
    ['individual', 'alta', Prioridad::Media], ['departamento', 'alta', Prioridad::Alta], ['todos', 'alta', Prioridad::Urgente],
]);

it('prioridad inicial: red implica urgencia alta', function () {
    $e = Empresa::factory()->soporteIt()->create();

    expect($this->it->prioridadInicial(['impacto' => 'todos', 'categoria' => 'red'], $e))->toBe(Prioridad::Urgente)
        ->and($this->it->prioridadInicial(['impacto' => 'individual', 'categoria' => 'software'], $e))->toBe(Prioridad::Baja);
});

it('asignar exige empleado asignado', function () {
    $t = Ticket::factory()->soporteIt()->create(['estado' => 'nuevo']);

    expect($this->it->validarTransicion($t, 'asignado'))->toBe('Asigna el ticket a un empleado primero.');
});

it('valores de soporte IT', function () {
    $e = Empresa::factory()->soporteIt()->create();

    expect($this->it->slaMinutos(Prioridad::Urgente, $e))->toBe(240)
        ->and($this->it->slaMinutos(Prioridad::Baja, $e))->toBe(4320)
        ->and($this->it->primeraRespuestaMinutos(Prioridad::Urgente))->toBe(30)
        ->and($this->it->primeraRespuestaMinutos(Prioridad::Baja))->toBe(480)
        ->and($this->it->estadosPausaSla())->toBe(['pendiente_usuario'])
        ->and($this->it->estadoReapertura(Ticket::factory()->soporteIt()->create()))->toBe('en_curso');
});

it('los otros sectores no tienen SLA de primera respuesta', function () {
    expect(Sectores::para(SectorClave::Taller)->primeraRespuestaMinutos(Prioridad::Alta))->toBeNull();
});
