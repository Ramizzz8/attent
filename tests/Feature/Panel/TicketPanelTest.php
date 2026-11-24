<?php

use App\Models\Empresa;
use App\Models\Ticket;
use App\Models\User;
use App\Tickets\EnviarMensaje;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->empresa = Empresa::factory()->soporteIt()->create();
    $this->yo = User::factory()->for($this->empresa)->create();
    $this->ticket = Ticket::factory()->create(['empresa_id' => $this->empresa->id, 'numero' => 4, 'estado' => 'nuevo']);
});

it('el ticket de otra empresa da 404 en detalle, estado y asignación', function () {
    $ajeno = Ticket::factory()->soporteIt()->create(['numero' => 77]);

    $this->actingAs($this->yo)->get('/panel/tickets/77')->assertNotFound();
    $this->actingAs($this->yo)->post('/panel/tickets/77/estado', ['destino' => 'asignado'])->assertNotFound();
    $this->actingAs($this->yo)->post('/panel/tickets/77/asignar', ['user_id' => $this->yo->id])->assertNotFound();

    expect($ajeno->fresh()->estado)->toBe('nuevo');
});

it('el detalle incluye notas internas, destinos y empleados', function () {
    app(EnviarMensaje::class)->ejecutar($this->ticket, $this->yo, 'Nota', interno: true);

    $this->actingAs($this->yo)->get('/panel/tickets/4')->assertInertia(fn (Assert $p) => $p
        ->component('panel/ticket')
        ->where('ticket.numero', 4)
        ->where('ticket.mensajes.0.interno', true)
        ->has('destinos', 1)
        ->where('destinos.0.estado', 'asignado')
        ->has('empleados', 1));
});

it('asignar en soporte IT mueve el ticket nuevo a asignado', function () {
    $this->actingAs($this->yo)->post('/panel/tickets/4/asignar', ['user_id' => $this->yo->id])->assertRedirect();

    $t = $this->ticket->fresh();
    expect($t->asignado_a)->toBe($this->yo->id)
        ->and($t->estado)->toBe('asignado')
        ->and($t->eventos()->where('tipo', 'asignado')->count())->toBe(1);
});

it('no se puede asignar a un usuario de otra empresa', function () {
    $ajeno = User::factory()->create();

    $this->actingAs($this->yo)->post('/panel/tickets/4/asignar', ['user_id' => $ajeno->id])->assertSessionHasErrors('user_id');

    expect($this->ticket->fresh()->asignado_a)->toBeNull();
});

it('se puede quitar la asignación', function () {
    $this->ticket->update(['asignado_a' => $this->yo->id, 'estado' => 'en_curso']);

    $this->actingAs($this->yo)->post('/panel/tickets/4/asignar', ['user_id' => null]);

    expect($this->ticket->fresh()->asignado_a)->toBeNull();
});

it('cambia el estado desde el panel', function () {
    $this->ticket->update(['asignado_a' => $this->yo->id, 'estado' => 'asignado']);

    $this->actingAs($this->yo)->post('/panel/tickets/4/estado', ['destino' => 'en_curso'])->assertRedirect();

    expect($this->ticket->fresh()->estado)->toBe('en_curso');
});

it('una transición no permitida vuelve con el error', function () {
    $this->actingAs($this->yo)->post('/panel/tickets/4/estado', ['destino' => 'cerrado'])
        ->assertSessionHasErrors(['destino' => 'No se puede pasar de «Nuevo» a «Cerrado».']);
});
