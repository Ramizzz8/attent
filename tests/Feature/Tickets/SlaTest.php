<?php

use App\Enums\Prioridad;
use App\Models\Ticket;
use App\Tickets\CambiarEstado;
use App\Tickets\Sla;

function ticketIt(array $attrs = []): Ticket
{
    // Prioridad media en soporte IT: 1440 minutos (24 h).
    return Ticket::factory()->soporteIt()->create([
        'estado' => 'en_curso', 'prioridad' => Prioridad::Media,
        'verificado_en' => now(), 'sla_vence_en' => now()->addMinutes(1440), ...$attrs,
    ]);
}

it('pausa y reanuda el SLA desplazando el vencimiento', function () {
    $this->freezeSecond();
    $t = ticketIt();
    $vence = $t->sla_vence_en->copy();
    $cambiar = app(CambiarEstado::class);

    $t = $cambiar->ejecutar($t, 'pendiente_usuario', null);
    expect($t->sla_pausado_en)->not->toBeNull()->and($t->estadoSla())->toBe('pausado');

    $this->travel(2)->hours();
    $t = $cambiar->ejecutar($t, 'en_curso', null);

    expect($t->sla_pausado_en)->toBeNull()
        ->and($t->sla_vence_en->equalTo($vence->addHours(2)))->toBeTrue()
        ->and($t->sla_pausa_segundos)->toBe(7200);
});

it('estado del SLA: ok, en riesgo y vencido', function () {
    $this->freezeSecond();
    $t = ticketIt();
    expect($t->estadoSla())->toBe('ok');

    $this->travel(1440 - 280)->minutes(); // quedan 280 min < 20 % de 1440 (288)
    expect($t->estadoSla())->toBe('en_riesgo');

    $this->travel(281)->minutes();
    expect($t->estadoSla())->toBe('vencido');
});

it('al llegar al estado objetivo se evalúa el cumplimiento', function () {
    $this->freezeSecond();
    $aTiempo = app(CambiarEstado::class)->ejecutar(ticketIt(), 'resuelto', null);
    $tarde = ticketIt();
    $this->travel(25)->hours();
    $tarde = app(CambiarEstado::class)->ejecutar($tarde, 'resuelto', null);

    expect($aTiempo->sla_cumplido)->toBeTrue()->and($aTiempo->estadoSla())->toBe('cumplido')
        ->and($tarde->sla_cumplido)->toBeFalse()->and($tarde->estadoSla())->toBe('incumplido');
});

it('recalcular por cambio de prioridad conserva el tiempo pausado', function () {
    $this->freezeSecond();
    $t = ticketIt(['sla_pausa_segundos' => 3600]);
    $t->prioridad = Prioridad::Urgente; // 240 min

    app(Sla::class)->recalcular($t);

    expect($t->sla_vence_en->equalTo(now()->addMinutes(240 + 60)))->toBeTrue();
});
