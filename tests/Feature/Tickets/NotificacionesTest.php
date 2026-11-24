<?php

use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\EstadoCambiado;
use App\Notifications\NuevoMensaje;
use App\Tickets\CambiarEstado;
use App\Tickets\EnviarMensaje;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Notification::fake();
    $this->empresa = Empresa::factory()->soporteIt()->create();
    $this->cliente = Cliente::factory()->for($this->empresa)->create();
    $this->empleado = User::factory()->for($this->empresa)->create();
    $this->ticket = Ticket::factory()->create([
        'empresa_id' => $this->empresa->id, 'cliente_id' => $this->cliente->id,
        'estado' => 'asignado', 'asignado_a' => $this->empleado->id,
    ]);
});

it('un cambio de estado avisa al cliente', function () {
    app(CambiarEstado::class)->ejecutar($this->ticket, 'en_curso', $this->empleado);

    Notification::assertSentTo($this->cliente, EstadoCambiado::class, fn ($n) => $n->ticket->estado === 'en_curso');
});

it('no avisa al cliente de un cambio que ha hecho él mismo', function () {
    $this->ticket->update(['estado' => 'pendiente_usuario']);

    app(EnviarMensaje::class)->ejecutar($this->ticket, $this->cliente, 'Ya está probado');

    Notification::assertNotSentTo($this->cliente, EstadoCambiado::class);
});

it('las notificaciones van en cola', function () {
    app(CambiarEstado::class)->ejecutar($this->ticket, 'en_curso', $this->empleado);

    Notification::assertSentTo($this->cliente, EstadoCambiado::class, fn ($n) => $n instanceof Illuminate\Contracts\Queue\ShouldQueue);
});

it('un mensaje del equipo avisa al cliente', function () {
    app(EnviarMensaje::class)->ejecutar($this->ticket, $this->empleado, 'Lo estamos mirando');

    Notification::assertSentTo($this->cliente, NuevoMensaje::class);
});

it('una nota interna no avisa al cliente', function () {
    app(EnviarMensaje::class)->ejecutar($this->ticket, $this->empleado, 'Nota', interno: true);

    Notification::assertNothingSentTo($this->cliente);
});

it('un mensaje del cliente avisa al empleado asignado', function () {
    app(EnviarMensaje::class)->ejecutar($this->ticket, $this->cliente, 'Sigue sin funcionar');

    Notification::assertSentTo($this->empleado, NuevoMensaje::class);
    Notification::assertNotSentTo($this->cliente, NuevoMensaje::class);
});

it('el email incluye un enlace mágico al ticket', function () {
    app(EnviarMensaje::class)->ejecutar($this->ticket, $this->empleado, 'Hola');

    Notification::assertSentTo($this->cliente, NuevoMensaje::class, function ($n) {
        $mail = $n->toMail($this->cliente);

        return str_contains($mail->actionUrl, '/e/'.$this->cliente->id.'/'.$this->ticket->id) && str_contains($mail->actionUrl, 'signature=');
    });
});

it('no avisa de tickets sin verificar', function () {
    $this->ticket->update(['verificado_en' => null]);

    app(CambiarEstado::class)->ejecutar($this->ticket, 'en_curso', $this->empleado);

    Notification::assertNothingSent();
});
