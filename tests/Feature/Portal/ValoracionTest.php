<?php

use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\Ticket;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->empresa = Empresa::factory()->soporteIt()->create(['slug' => 'soporte-tic']);
    $this->cliente = Cliente::factory()->for($this->empresa)->create();
    $this->ticket = Ticket::factory()->create([
        'empresa_id' => $this->empresa->id, 'cliente_id' => $this->cliente->id, 'numero' => 4, 'estado' => 'resuelto', 'resuelto_en' => now(),
    ]);
});

it('el cliente valora una incidencia resuelta una sola vez', function () {
    $this->actingAs($this->cliente, 'cliente')->post('/soporte-tic/tickets/4/valoracion', ['valoracion' => 5, 'comentario' => 'Genial'])
        ->assertRedirect('/soporte-tic/tickets/4');
    $this->actingAs($this->cliente, 'cliente')->post('/soporte-tic/tickets/4/valoracion', ['valoracion' => 1])
        ->assertSessionHasErrors('valoracion');

    expect($this->ticket->fresh()->valoracion)->toBe(5)
        ->and($this->ticket->fresh()->valoracion_comentario)->toBe('Genial');
});

it('rechaza notas fuera de rango', function (mixed $nota) {
    $this->actingAs($this->cliente, 'cliente')->post('/soporte-tic/tickets/4/valoracion', ['valoracion' => $nota])->assertSessionHasErrors('valoracion');
})->with([0, 6, 'cinco']);

it('no se valora una incidencia que no está resuelta', function () {
    $this->ticket->update(['estado' => 'en_curso', 'resuelto_en' => null]);

    $this->actingAs($this->cliente, 'cliente')->post('/soporte-tic/tickets/4/valoracion', ['valoracion' => 4])->assertSessionHasErrors('valoracion');
});

it('otro cliente no puede valorar', function () {
    $otro = Cliente::factory()->for($this->empresa)->create();

    $this->actingAs($otro, 'cliente')->post('/soporte-tic/tickets/4/valoracion', ['valoracion' => 4])->assertNotFound();
});

it('la vista del portal indica si se puede valorar y el panel muestra la valoración', function () {
    $this->actingAs($this->cliente, 'cliente')->get('/soporte-tic/tickets/4')->assertInertia(fn (Assert $p) => $p
        ->where('ticket.puedeValorar', true)
        ->where('ticket.valoracion', null));

    $this->ticket->update(['valoracion' => 4, 'valoracion_comentario' => 'Bien']);
    $this->actingAs(User::factory()->for($this->empresa)->create())->get('/panel/tickets/4')->assertInertia(fn (Assert $p) => $p
        ->where('ticket.valoracion', 4)
        ->where('ticket.valoracionComentario', 'Bien'));
});
