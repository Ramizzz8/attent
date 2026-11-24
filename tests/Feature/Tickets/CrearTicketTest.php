<?php

use App\Enums\Prioridad;
use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\Ticket;
use App\Tickets\CrearTicket;

function datosTicket(array $extra = []): array
{
    return ['asunto' => 'No arranca', 'descripcion' => 'Hace un ruido raro', 'categoria' => 'mecanica', ...$extra];
}

it('numera por empresa empezando en 1', function () {
    [$a, $b] = Empresa::factory()->taller()->count(2)->create();
    $ca = Cliente::factory()->for($a)->create();
    $cb = Cliente::factory()->for($b)->create();
    $crear = app(CrearTicket::class);

    $nums = [
        $crear->ejecutar($a, $ca, datosTicket(), true)->numero,
        $crear->ejecutar($a, $ca, datosTicket(), true)->numero,
        $crear->ejecutar($a, $ca, datosTicket(), true)->numero,
        $crear->ejecutar($b, $cb, datosTicket(), true)->numero,
    ];

    expect($nums)->toBe([1, 2, 3, 1]);
});

it('un ticket verificado arranca con estado inicial, prioridad, SLA y evento', function () {
    $this->freezeSecond();
    $e = Empresa::factory()->taller()->create();
    $t = app(CrearTicket::class)->ejecutar($e, Cliente::factory()->for($e)->create(), datosTicket(['datos' => ['matricula' => '1234ABC']]), true);

    expect($t->estado)->toBe('recibido')
        ->and($t->prioridad)->toBe(Prioridad::Media)
        ->and($t->verificado_en->equalTo(now()))->toBeTrue()
        ->and($t->sla_vence_en->equalTo(now()->addMinutes(2880)))->toBeTrue()
        ->and($t->datos)->toBe(['matricula' => '1234ABC'])
        ->and($t->eventos()->where('tipo', 'creado')->count())->toBe(1);
});

it('un ticket sin verificar no tiene SLA hasta verificarse', function () {
    $this->freezeSecond();
    $e = Empresa::factory()->taller()->create();
    $t = app(CrearTicket::class)->ejecutar($e, Cliente::factory()->for($e)->create(), datosTicket(), false);

    expect($t->verificado_en)->toBeNull()->and($t->sla_vence_en)->toBeNull();

    $this->travel(2)->hours();
    app(CrearTicket::class)->verificar($t);

    expect($t->fresh()->sla_vence_en->equalTo(now()->addMinutes(2880)))->toBeTrue();
});

it('soporte IT calcula prioridad por matriz y vencimiento de primera respuesta', function () {
    $this->freezeSecond();
    $e = Empresa::factory()->soporteIt()->create();
    $t = app(CrearTicket::class)->ejecutar($e, Cliente::factory()->for($e)->create(),
        ['asunto' => 'Sin red', 'descripcion' => 'Caída general', 'categoria' => 'red', 'impacto' => 'todos'], true);

    expect($t->prioridad)->toBe(Prioridad::Urgente)
        ->and($t->urgencia)->toBe('alta')
        ->and($t->sla_vence_en->equalTo(now()->addMinutes(240)))->toBeTrue()
        ->and($t->primera_respuesta_vence_en->equalTo(now()->addMinutes(30)))->toBeTrue();
});

it('no acepta un cliente de otra empresa', function () {
    [$a, $b] = Empresa::factory()->taller()->count(2)->create();
    app(CrearTicket::class)->ejecutar($a, Cliente::factory()->for($b)->create(), datosTicket(), true);
})->throws(InvalidArgumentException::class);
