<?php

use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\Presupuesto;
use App\Models\Ticket;
use App\Tickets\ReabrirTicket;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;

function resuelto(string $sector, array $attrs = []): Ticket
{
    $e = Empresa::factory()->state(['sector' => $sector])->create();

    return Ticket::factory()->create([
        'empresa_id' => $e->id,
        'estado' => $e->motor()->estadoResuelto(),
        'resuelto_en' => now(),
        'sla_cumplido' => true,
        ...$attrs,
    ]);
}

it('el cliente reabre a los 6 días y vuelve al estado de trabajo con nuevo plazo', function () {
    $this->freezeSecond();
    $t = resuelto('soporte_it');
    $this->travel(6)->days();

    $t = app(ReabrirTicket::class)->ejecutar($t, $t->cliente, 'Sigue fallando');

    expect($t->estado)->toBe('en_curso')
        ->and($t->resuelto_en)->toBeNull()
        ->and($t->sla_cumplido)->toBeNull()
        ->and($t->sla_vence_en->greaterThan(now()))->toBeTrue()
        ->and($t->eventos()->where('tipo', 'reabierto')->value('motivo'))->toBe('Sigue fallando');
});

it('no se puede reabrir pasados 7 días', function () {
    $t = resuelto('soporte_it');
    $this->travel(8)->days();

    app(ReabrirTicket::class)->ejecutar($t, $t->cliente, 'Otra vez');
})->throws(ValidationException::class);

it('solo reabre quien abrió la incidencia', function () {
    $t = resuelto('soporte_it');
    $otro = Cliente::factory()->create(['empresa_id' => $t->empresa_id]);

    app(ReabrirTicket::class)->ejecutar($t, $otro, 'No es mía');
})->throws(Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);

it('un taller con presupuesto rechazado reabre en diagnóstico', function () {
    $t = resuelto('taller');
    Presupuesto::factory()->for($t)->create(['estado' => 'rechazado']);

    expect(app(ReabrirTicket::class)->ejecutar($t, $t->cliente, 'Hace ruido')->estado)->toBe('en_diagnostico');
});

it('se reabre desde el portal y la vista indica si se puede', function () {
    $t = resuelto('fincas', ['numero' => 3]);
    $slug = $t->empresa->slug;

    $this->actingAs($t->cliente, 'cliente')->get("/{$slug}/tickets/3")->assertInertia(fn (Assert $p) => $p->where('ticket.puedeReabrir', true));
    $this->actingAs($t->cliente, 'cliente')->post("/{$slug}/tickets/3/reabrir", ['motivo' => 'Vuelve a gotear'])->assertRedirect("/{$slug}/tickets/3");

    expect($t->fresh()->estado)->toBe('en_revision');
});

it('el comando cierra los resueltos de más de 7 días y no toca el taller', function () {
    $it = resuelto('soporte_it', ['resuelto_en' => now()->subDays(8)]);
    $reciente = resuelto('soporte_it', ['resuelto_en' => now()->subDays(2)]);
    $taller = resuelto('taller', ['resuelto_en' => now()->subDays(30)]);

    $this->artisan('atiende:cerrar-resueltos')->assertSuccessful();

    expect($it->fresh()->estado)->toBe('cerrado')
        ->and($it->fresh()->cerrado_en)->not->toBeNull()
        ->and($reciente->fresh()->estado)->toBe('resuelto')
        ->and($taller->fresh()->estado)->toBe('entregado');
});

it('el cierre automático está programado a diario', function () {
    $eventos = collect(app(Illuminate\Console\Scheduling\Schedule::class)->events());

    expect($eventos->contains(fn ($e) => str_contains($e->command ?? '', 'atiende:cerrar-resueltos')))->toBeTrue();
});
