<?php

use App\Enums\Prioridad;
use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\Ticket;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->empresa = Empresa::factory()->soporteIt()->create();
    $this->yo = User::factory()->for($this->empresa)->create();
});

function ticketDe(Empresa $e, array $attrs = []): Ticket
{
    return Ticket::factory()->create(['empresa_id' => $e->id, ...$attrs]);
}

function numerosBandeja($test, string $query = ''): array
{
    $numeros = [];
    $test->actingAs($test->yo)->get('/panel'.$query)
        ->assertInertia(function (Assert $p) use (&$numeros) {
            $p->component('panel/bandeja');
            $numeros = collect($p->toArray()['props']['tickets']['data'])->pluck('numero')->sort()->values()->all();
        });

    return $numeros;
}

it('los invitados van al login', function () {
    $this->get('/panel')->assertRedirect('/login');
});

it('muestra solo tickets verificados de mi empresa', function () {
    ticketDe($this->empresa, ['numero' => 1]);
    ticketDe($this->empresa, ['numero' => 2, 'verificado_en' => null]);
    ticketDe(Empresa::factory()->create(), ['numero' => 3]);

    expect(numerosBandeja($this))->toBe([1]);
});

it('por defecto oculta los terminados y con estado=todos los incluye', function () {
    ticketDe($this->empresa, ['numero' => 1, 'estado' => 'en_curso']);
    ticketDe($this->empresa, ['numero' => 2, 'estado' => 'cerrado']);

    expect(numerosBandeja($this))->toBe([1])
        ->and(numerosBandeja($this, '?estado=todos'))->toBe([1, 2])
        ->and(numerosBandeja($this, '?estado=cerrado'))->toBe([2]);
});

it('filtra por asignación', function () {
    ticketDe($this->empresa, ['numero' => 1, 'asignado_a' => $this->yo->id]);
    ticketDe($this->empresa, ['numero' => 2]);
    ticketDe($this->empresa, ['numero' => 3, 'asignado_a' => User::factory()->for($this->empresa)]);

    expect(numerosBandeja($this, '?asignado=yo'))->toBe([1])
        ->and(numerosBandeja($this, '?asignado=sin_asignar'))->toBe([2]);
});

it('filtra por SLA vencido y en riesgo', function () {
    $this->freezeSecond();
    ticketDe($this->empresa, ['numero' => 1, 'prioridad' => Prioridad::Media, 'sla_vence_en' => now()->subMinute()]);
    // Media en IT = 1440 min; en riesgo si quedan menos de 288.
    ticketDe($this->empresa, ['numero' => 2, 'prioridad' => Prioridad::Media, 'sla_vence_en' => now()->addMinutes(100)]);
    ticketDe($this->empresa, ['numero' => 3, 'prioridad' => Prioridad::Media, 'sla_vence_en' => now()->addMinutes(1000)]);
    ticketDe($this->empresa, ['numero' => 4, 'prioridad' => Prioridad::Media, 'sla_vence_en' => now()->subMinute(), 'sla_pausado_en' => now()]);

    expect(numerosBandeja($this, '?sla=vencido'))->toBe([1])
        ->and(numerosBandeja($this, '?sla=en_riesgo'))->toBe([2]);
});

it('busca por número, asunto y email del cliente', function () {
    // Datos fijos: con nombres o emails aleatorios la búsqueda podría coincidir por casualidad.
    $c = Cliente::factory()->for($this->empresa)->create(['nombre' => 'Marta', 'email' => 'zzmarta@cole.es']);
    $otro = Cliente::factory()->for($this->empresa)->create(['nombre' => 'Pedro', 'email' => 'pedro@cole.es']);
    ticketDe($this->empresa, ['numero' => 10, 'asunto' => 'Proyector del aula 3', 'cliente_id' => $otro->id]);
    ticketDe($this->empresa, ['numero' => 11, 'asunto' => 'Teclado roto', 'cliente_id' => $c->id]);
    ticketDe($this->empresa, ['numero' => 12, 'asunto' => 'Sin red', 'cliente_id' => $otro->id]);

    expect(numerosBandeja($this, '?q=proyector'))->toBe([10])
        ->and(numerosBandeja($this, '?q=zzmarta@'))->toBe([11])
        ->and(numerosBandeja($this, '?q=12'))->toBe([12]);
});

it('ordena por vencimiento de SLA', function () {
    ticketDe($this->empresa, ['numero' => 1, 'sla_vence_en' => now()->addHours(5)]);
    ticketDe($this->empresa, ['numero' => 2, 'sla_vence_en' => now()->addHour()]);

    $this->actingAs($this->yo)->get('/panel')->assertInertia(fn (Assert $p) => $p
        ->where('tickets.data.0.numero', 2)
        ->where('tickets.data.1.numero', 1));
});
