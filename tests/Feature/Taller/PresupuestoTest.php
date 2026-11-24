<?php

use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\Presupuesto;
use App\Models\Ticket;
use App\Models\User;
use App\Models\Vehiculo;
use App\Notifications\EstadoCambiado;
use App\Notifications\PresupuestoEnviado;
use App\Taller\EnviarPresupuesto;
use App\Tickets\CambiarEstado;
use App\Tickets\CrearTicket;
use App\Tickets\TransicionNoPermitida;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Notification::fake();
    $this->empresa = Empresa::factory()->taller()->create(['slug' => 'talleres-ruiz']);
    $this->cliente = Cliente::factory()->for($this->empresa)->create();
    $this->mecanico = User::factory()->for($this->empresa)->create();
    $this->ticket = Ticket::factory()->create([
        'empresa_id' => $this->empresa->id, 'cliente_id' => $this->cliente->id, 'numero' => 8, 'estado' => 'en_diagnostico',
    ]);
});

function lineas(): array
{
    return [
        ['tipo' => 'pieza', 'concepto' => 'Pastillas de freno', 'cantidad' => 2, 'precio_unitario' => 45.50],
        ['tipo' => 'mano_obra', 'concepto' => 'Mano de obra (1 h)', 'cantidad' => 1, 'precio_unitario' => 60],
    ];
}

it('calcula base, IVA y total', function () {
    $p = app(EnviarPresupuesto::class)->ejecutar($this->ticket, lineas(), 21, $this->mecanico);

    expect($p->base())->toBe(151.0)->and($p->iva())->toBe(31.71)->and($p->total())->toBe(182.71);
});

it('enviar el presupuesto crea la versión 1, cambia el estado y avisa con su propio email', function () {
    $p = app(EnviarPresupuesto::class)->ejecutar($this->ticket, lineas(), 21, $this->mecanico);

    expect($p->version)->toBe(1)->and($p->lineas)->toHaveCount(2)
        ->and($this->ticket->fresh()->estado)->toBe('presupuesto_enviado');
    Notification::assertSentTo($this->cliente, PresupuestoEnviado::class);
    Notification::assertNotSentTo($this->cliente, EstadoCambiado::class);
});

it('el empleado envía el presupuesto desde el panel', function () {
    $this->actingAs($this->mecanico)
        ->post('/panel/tickets/8/presupuestos', ['lineas' => lineas(), 'iva_pct' => 21])
        ->assertSessionHasNoErrors();

    expect(Presupuesto::sole()->total())->toBe(182.71);
});

it('el presupuesto necesita al menos una línea válida', function () {
    $this->actingAs($this->mecanico)
        ->post('/panel/tickets/8/presupuestos', ['lineas' => [['tipo' => 'pieza', 'concepto' => '', 'cantidad' => 0, 'precio_unitario' => -1]], 'iva_pct' => 21])
        ->assertSessionHasErrors(['lineas.0.concepto', 'lineas.0.cantidad', 'lineas.0.precio_unitario']);
});

it('no se envía presupuesto desde un estado que no toca', function () {
    $this->ticket->update(['estado' => 'recibido']);

    app(EnviarPresupuesto::class)->ejecutar($this->ticket, lineas(), 21, $this->mecanico);
})->throws(TransicionNoPermitida::class);

it('el cliente aprueba desde el portal y ya se puede reparar', function () {
    app(EnviarPresupuesto::class)->ejecutar($this->ticket, lineas(), 21, $this->mecanico);

    $this->actingAs($this->cliente, 'cliente')
        ->post('/talleres-ruiz/tickets/8/presupuesto', ['aprueba' => true])
        ->assertRedirect('/talleres-ruiz/tickets/8');

    expect($this->ticket->fresh()->estado)->toBe('aprobado')
        ->and(Presupuesto::sole()->estado)->toBe('aprobado');
    expect(app(CambiarEstado::class)->ejecutar($this->ticket->fresh(), 'en_reparacion', $this->mecanico)->estado)->toBe('en_reparacion');
});

it('tras un rechazo se puede enviar una nueva versión', function () {
    app(EnviarPresupuesto::class)->ejecutar($this->ticket, lineas(), 21, $this->mecanico);
    $this->actingAs($this->cliente, 'cliente')->post('/talleres-ruiz/tickets/8/presupuesto', ['aprueba' => false, 'comentario' => 'Muy caro']);
    expect($this->ticket->fresh()->estado)->toBe('rechazado');

    $v2 = app(EnviarPresupuesto::class)->ejecutar($this->ticket->fresh(), [lineas()[1]], 21, $this->mecanico);

    expect($v2->version)->toBe(2)
        ->and($this->ticket->fresh()->estado)->toBe('presupuesto_enviado')
        ->and(Presupuesto::where('version', 1)->value('comentario_cliente'))->toBe('Muy caro');
});

it('el cliente no puede responder una versión antigua ni responder dos veces', function () {
    app(EnviarPresupuesto::class)->ejecutar($this->ticket, lineas(), 21, $this->mecanico);
    $this->actingAs($this->cliente, 'cliente')->post('/talleres-ruiz/tickets/8/presupuesto', ['aprueba' => true]);

    $this->actingAs($this->cliente, 'cliente')
        ->post('/talleres-ruiz/tickets/8/presupuesto', ['aprueba' => false])
        ->assertSessionHasErrors('presupuesto');

    expect($this->ticket->fresh()->estado)->toBe('aprobado');
});

it('otro cliente no puede responder el presupuesto', function () {
    app(EnviarPresupuesto::class)->ejecutar($this->ticket, lineas(), 21, $this->mecanico);
    $otro = Cliente::factory()->for($this->empresa)->create();

    $this->actingAs($otro, 'cliente')->post('/talleres-ruiz/tickets/8/presupuesto', ['aprueba' => true])->assertNotFound();
});

it('no se puede enviar presupuesto de un vehículo entregado', function () {
    $this->ticket->update(['estado' => 'entregado']);

    app(EnviarPresupuesto::class)->ejecutar($this->ticket, lineas(), 21, $this->mecanico);
})->throws(TransicionNoPermitida::class);

it('la misma matrícula escrita distinto es el mismo vehículo', function () {
    $crear = app(CrearTicket::class);
    $base = ['asunto' => 'Revisión', 'descripcion' => 'Anual', 'categoria' => 'revision'];

    $a = $crear->ejecutar($this->empresa, $this->cliente, [...$base, 'datos' => ['matricula' => '1234 abc', 'marca' => 'Seat']], true);
    $b = $crear->ejecutar($this->empresa, $this->cliente, [...$base, 'datos' => ['matricula' => '1234-ABC']], true);

    expect(Vehiculo::count())->toBe(1)
        ->and(Vehiculo::sole()->matricula)->toBe('1234ABC')
        ->and($a->vehiculo_id)->toBe($b->vehiculo_id);
});

it('la bandeja busca por matrícula', function () {
    $v = Vehiculo::create(['empresa_id' => $this->empresa->id, 'cliente_id' => $this->cliente->id, 'matricula' => '9999XYZ']);
    $this->ticket->update(['vehiculo_id' => $v->id]);
    Ticket::factory()->create(['empresa_id' => $this->empresa->id, 'numero' => 9]);

    $this->actingAs($this->mecanico)->get('/panel?q=9999 xyz')->assertInertia(fn (Assert $p) => $p
        ->has('tickets.data', 1)
        ->where('tickets.data.0.numero', 8));
});

it('el detalle del panel incluye presupuestos y vehículo', function () {
    $v = Vehiculo::create(['empresa_id' => $this->empresa->id, 'cliente_id' => $this->cliente->id, 'matricula' => '1111AAA', 'marca' => 'Seat']);
    $this->ticket->update(['vehiculo_id' => $v->id]);
    app(EnviarPresupuesto::class)->ejecutar($this->ticket, lineas(), 21, $this->mecanico);

    $this->actingAs($this->mecanico)->get('/panel/tickets/8')->assertInertia(fn (Assert $p) => $p
        ->where('ticket.vehiculo.matricula', '1111AAA')
        ->has('ticket.presupuestos', 1)
        ->where('ticket.presupuestos.0.total', 182.71));
});

it('el cliente ve el presupuesto vigente en el portal', function () {
    app(EnviarPresupuesto::class)->ejecutar($this->ticket, lineas(), 21, $this->mecanico);

    $this->actingAs($this->cliente, 'cliente')->get('/talleres-ruiz/tickets/8')->assertInertia(fn (Assert $p) => $p
        ->where('ticket.presupuesto.version', 1)
        ->where('ticket.presupuesto.total', 182.71)
        ->where('ticket.presupuesto.puedeResponder', true));
});
