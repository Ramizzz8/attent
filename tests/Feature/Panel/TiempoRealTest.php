<?php

use App\Broadcasting\CanalEmpresa;
use App\Events\TicketActualizado;
use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\Ticket;
use App\Models\User;
use App\Tickets\CambiarEstado;
use App\Tickets\CrearTicket;
use App\Tickets\EnviarMensaje;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    $this->empresa = Empresa::factory()->soporteIt()->create();
    $this->empleado = User::factory()->for($this->empresa)->create();
});

it('emite al canal privado de la empresa cuando cambia el estado', function () {
    $t = Ticket::factory()->create(['empresa_id' => $this->empresa->id, 'estado' => 'asignado', 'asignado_a' => $this->empleado->id]);
    Event::fake([TicketActualizado::class]);

    app(CambiarEstado::class)->ejecutar($t, 'en_curso', $this->empleado);

    Event::assertDispatched(TicketActualizado::class, function (TicketActualizado $e) use ($t) {
        $canal = $e->broadcastOn()[0];

        return $e->numero === $t->numero
            && $e->tipo === 'estado'
            && $canal instanceof PrivateChannel
            && $canal->name === "private-empresa.{$this->empresa->id}"
            && $e->broadcastAs() === 'ticket.actualizado';
    });
});

it('emite al llegar un ticket nuevo verificado, no uno sin verificar', function () {
    Event::fake([TicketActualizado::class]);
    $c = Cliente::factory()->for($this->empresa)->create();
    $datos = ['asunto' => 'A', 'descripcion' => 'B', 'categoria' => 'red', 'impacto' => 'todos'];

    app(CrearTicket::class)->ejecutar($this->empresa, $c, $datos, false);
    Event::assertNotDispatched(TicketActualizado::class);

    app(CrearTicket::class)->ejecutar($this->empresa, $c, $datos, true);
    Event::assertDispatched(TicketActualizado::class, fn ($e) => $e->tipo === 'creado');
});

it('emite cuando llega un mensaje', function () {
    $t = Ticket::factory()->create(['empresa_id' => $this->empresa->id]);
    Event::fake([TicketActualizado::class]);

    app(EnviarMensaje::class)->ejecutar($t, $t->cliente, 'Hola');

    Event::assertDispatched(TicketActualizado::class, fn ($e) => $e->tipo === 'mensaje');
});

it('solo los empleados de la empresa entran en su canal', function () {
    $canal = new CanalEmpresa;

    expect($canal->join($this->empleado, $this->empresa->id))->toBeTrue()
        ->and($canal->join(User::factory()->create(), $this->empresa->id))->toBeFalse();
});
