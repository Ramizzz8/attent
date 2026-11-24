<?php

use App\Models\Presupuesto;
use App\Models\Ticket;
use App\Models\User;
use App\Tickets\CambiarEstado;
use App\Tickets\TransicionNoPermitida;

function cambiar(Ticket $t, string $destino, $actor = null, ?string $motivo = null, bool $sistema = false): Ticket
{
    return app(CambiarEstado::class)->ejecutar($t, $destino, $actor, $motivo, $sistema);
}

it('avanza por una transición permitida y lo registra', function () {
    $t = Ticket::factory()->taller()->create();
    $u = User::factory()->create(['empresa_id' => $t->empresa_id]);

    $t = cambiar($t, 'en_diagnostico', $u);

    $evento = $t->eventos()->where('tipo', 'estado')->sole();
    expect($t->estado)->toBe('en_diagnostico')
        ->and([$evento->de, $evento->a, $evento->user_id])->toBe(['recibido', 'en_diagnostico', $u->id]);
});

it('rechaza una transición no listada', function () {
    cambiar(Ticket::factory()->taller()->create(), 'entregado');
})->throws(TransicionNoPermitida::class);

it('rechaza un estado inexistente', function () {
    cambiar(Ticket::factory()->taller()->create(), 'volando');
})->throws(TransicionNoPermitida::class);

it('un empleado no puede forzar un estado reservado al sistema', function () {
    $t = Ticket::factory()->taller()->create(['estado' => 'en_diagnostico']);

    expect(fn () => cambiar($t, 'presupuesto_enviado'))->toThrow(TransicionNoPermitida::class)
        ->and(cambiar($t->fresh(), 'presupuesto_enviado', sistema: true)->estado)->toBe('presupuesto_enviado');
});

it('permite retroceder al estado anterior con motivo', function () {
    $t = cambiar(Ticket::factory()->soporteIt()->create(['estado' => 'asignado', 'asignado_a' => User::factory()]), 'en_curso');

    $t = cambiar($t, 'asignado', motivo: 'Me equivoqué de ticket');

    expect($t->estado)->toBe('asignado')
        ->and($t->eventos()->latest('id')->first()->motivo)->toBe('Me equivoqué de ticket');
});

it('retroceder sin motivo no está permitido', function () {
    $t = cambiar(Ticket::factory()->soporteIt()->create(['estado' => 'asignado', 'asignado_a' => User::factory()]), 'en_curso');
    cambiar($t, 'asignado');
})->throws(TransicionNoPermitida::class);

it('retroceder no se salta las reglas del sector', function () {
    $t = Ticket::factory()->taller()->create(['estado' => 'aprobado']);
    $p = Presupuesto::factory()->for($t)->create(['estado' => 'aprobado']);
    $t = cambiar($t, 'en_reparacion');
    $t = cambiar($t, 'listo_recoger');
    $p->update(['estado' => 'rechazado']);

    cambiar($t, 'en_reparacion', motivo: 'Falta una pieza');
})->throws(TransicionNoPermitida::class, 'No se puede reparar sin un presupuesto aprobado.');

it('un cambio basado en un estado obsoleto falla sin duplicar eventos', function () {
    $t = Ticket::factory()->soporteIt()->create(['estado' => 'asignado', 'asignado_a' => User::factory()]);
    $copia = Ticket::find($t->id);

    cambiar($t, 'en_curso');

    expect(fn () => cambiar($copia, 'en_curso'))->toThrow(TransicionNoPermitida::class)
        ->and($t->fresh()->estado)->toBe('en_curso')
        ->and($t->eventos()->where('tipo', 'estado')->count())->toBe(1);
});

it('marca resuelto_en y cerrado_en', function () {
    $this->freezeSecond();
    $t = cambiar(Ticket::factory()->soporteIt()->create(['estado' => 'en_curso']), 'resuelto');
    expect($t->resuelto_en->equalTo(now()))->toBeTrue()->and($t->cerrado_en)->toBeNull();

    $t = cambiar($t, 'cerrado');
    expect($t->cerrado_en->equalTo(now()))->toBeTrue();
});

it('lista los destinos posibles incluido el retroceso', function () {
    $t = cambiar(Ticket::factory()->soporteIt()->create(['estado' => 'asignado', 'asignado_a' => User::factory()]), 'en_curso');

    expect(collect(app(CambiarEstado::class)->destinosPosibles($t))->map(fn ($d) => [$d['estado'], $d['retroceso']])->all())
        ->toBe([['pendiente_usuario', false], ['resuelto', false], ['asignado', true]]);
});

it('marca como principal el siguiente paso lógico del sector', function () {
    $t = cambiar(Ticket::factory()->soporteIt()->create(['estado' => 'asignado', 'asignado_a' => User::factory()]), 'en_curso');

    expect(collect(app(CambiarEstado::class)->destinosPosibles($t))->map(fn ($d) => [$d['estado'], $d['principal']])->all())
        ->toBe([['pendiente_usuario', false], ['resuelto', true], ['asignado', false]]);
});
