<?php

use App\Models\Presupuesto;
use App\Models\Ticket;
use App\Portal\ProgresoTicket;

function situaciones(Ticket $t): array
{
    return collect(ProgresoTicket::progreso($t))->mapWithKeys(fn ($p) => [$p['estado'] => $p['situacion']])->all();
}

it('marca los estados hechos, el actual y los pendientes del recorrido', function () {
    $t = Ticket::factory()->taller()->create(['estado' => 'en_diagnostico']);
    $t->eventos()->create(['tipo' => 'estado', 'de' => 'recibido', 'a' => 'en_diagnostico']);

    expect(situaciones($t))->toBe([
        'recibido' => 'hecho', 'en_diagnostico' => 'actual', 'presupuesto_enviado' => 'pendiente',
        'en_reparacion' => 'pendiente', 'listo_recoger' => 'pendiente', 'entregado' => 'pendiente',
    ]);
});

it('un estado fuera del recorrido aparece como actual tras lo ya alcanzado', function () {
    $t = Ticket::factory()->taller()->create(['estado' => 'rechazado']);
    foreach ([['recibido', 'en_diagnostico'], ['en_diagnostico', 'presupuesto_enviado'], ['presupuesto_enviado', 'rechazado']] as [$de, $a]) {
        $t->eventos()->create(['tipo' => 'estado', 'de' => $de, 'a' => $a]);
    }

    expect(situaciones($t))->toBe([
        'recibido' => 'hecho', 'en_diagnostico' => 'hecho', 'presupuesto_enviado' => 'hecho',
        'rechazado' => 'actual', 'listo_recoger' => 'pendiente', 'entregado' => 'pendiente',
    ]);
});

it('no marca como hechos los pasos por los que la incidencia no pasó', function () {
    $t = Ticket::factory()->fincas()->create(['estado' => 'resuelta']);
    foreach ([['reportada', 'en_revision'], ['en_revision', 'resuelta']] as [$de, $a]) {
        $t->eventos()->create(['tipo' => 'estado', 'de' => $de, 'a' => $a]);
    }

    expect(situaciones($t))->toBe(['reportada' => 'hecho', 'en_revision' => 'hecho', 'resuelta' => 'actual']);
});

it('tras rechazar el presupuesto no promete la reparación', function () {
    $t = Ticket::factory()->taller()->create(['estado' => 'rechazado']);
    foreach ([['recibido', 'en_diagnostico'], ['en_diagnostico', 'presupuesto_enviado'], ['presupuesto_enviado', 'rechazado']] as [$de, $a]) {
        $t->eventos()->create(['tipo' => 'estado', 'de' => $de, 'a' => $a]);
    }

    expect(situaciones($t))->not->toHaveKey('en_reparacion');
});

it('solo sugiere reabrir si todavía se puede', function () {
    $reciente = Ticket::factory()->soporteIt()->create(['estado' => 'resuelto', 'resuelto_en' => now()]);
    $antigua = Ticket::factory()->soporteIt()->create(['estado' => 'resuelto', 'resuelto_en' => now()->subDays(10)]);

    expect(ProgresoTicket::accionPendiente($reciente)['texto'])->toContain('reabre')
        ->and(ProgresoTicket::accionPendiente($antigua)['texto'] ?? '')->not->toContain('reabre');
});

it('una incidencia descartada termina ahí, sin pendientes', function () {
    $t = Ticket::factory()->fincas()->create(['estado' => 'descartada']);

    $s = situaciones($t);
    expect(array_key_last($s))->toBe('descartada')
        ->and($s['descartada'])->toBe('actual')
        ->and(in_array('pendiente', $s, true))->toBeFalse();
});

it('indica lo que tiene que hacer el cliente', function () {
    $presupuesto = Ticket::factory()->taller()->create(['estado' => 'presupuesto_enviado']);
    Presupuesto::factory()->for($presupuesto)->create(['estado' => 'pendiente']);
    $esperandole = Ticket::factory()->soporteIt()->create(['estado' => 'pendiente_usuario']);
    $resuelta = Ticket::factory()->soporteIt()->create(['estado' => 'resuelto', 'resuelto_en' => now()]);
    $enCurso = Ticket::factory()->soporteIt()->create(['estado' => 'en_curso']);

    expect(ProgresoTicket::accionPendiente($presupuesto)['tipo'])->toBe('presupuesto')
        ->and(ProgresoTicket::accionPendiente($esperandole)['tipo'])->toBe('responder')
        ->and(ProgresoTicket::accionPendiente($resuelta)['tipo'])->toBe('valorar')
        ->and(ProgresoTicket::accionPendiente($enCurso))->toBeNull();
});

it('la vista del portal incluye el progreso y la acción pendiente', function () {
    $t = Ticket::factory()->soporteIt()->create(['estado' => 'pendiente_usuario', 'numero' => 7]);
    $slug = $t->empresa->slug;

    $this->actingAs($t->cliente, 'cliente')->get("/{$slug}/tickets/7")->assertInertia(fn ($p) => $p
        ->has('ticket.progreso')
        ->where('ticket.accionPendiente.tipo', 'responder'));
});
