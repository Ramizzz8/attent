<?php

require_once __DIR__.'/FincasHelpers.php';

use App\Models\Mensaje;
use App\Models\Proveedor;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\ProveedorAsignado;
use App\Fincas\EnlacesProveedor;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Notification::fake();
    montarFincas($this);
    $this->gestor = User::factory()->for($this->empresa)->create();
    $this->ticket = Ticket::factory()->create([
        'empresa_id' => $this->empresa->id, 'numero' => 3, 'estado' => 'en_revision', 'categoria' => 'averia',
        'edificio_id' => $this->edificio->id, 'zona_id' => $this->portal->id,
    ]);
});

function asignarPaco($test): void
{
    $test->actingAs($test->gestor)->post('/panel/tickets/3/proveedor', ['proveedor_id' => $test->fontanero->id]);
}

it('no asigna un proveedor que no atiende el edificio', function () {
    $otro = Proveedor::create(['empresa_id' => $this->empresa->id, 'nombre' => 'Ascensores Sur', 'oficio' => 'Ascensorista', 'email' => 'sur@x.test']);
    $otro->edificios()->attach($this->otroEdificio);

    $this->actingAs($this->gestor)->post('/panel/tickets/3/proveedor', ['proveedor_id' => $otro->id])->assertSessionHasErrors('proveedor_id');

    expect($this->ticket->fresh()->estado)->toBe('en_revision');
});

it('asignar un proveedor cambia el estado y le envía el enlace', function () {
    asignarPaco($this);

    $t = $this->ticket->fresh();
    expect($t->estado)->toBe('asignada_proveedor')->and($t->proveedor_id)->toBe($this->fontanero->id);
    Notification::assertSentTo($this->fontanero, ProveedorAsignado::class);
});

it('el proveedor abre la incidencia con su enlace y caduca a los 30 días', function () {
    asignarPaco($this);
    $url = EnlacesProveedor::ver($this->ticket->fresh());

    $this->get($url)->assertInertia(fn (Assert $p) => $p->component('proveedor/ticket')->where('ticket.asunto', $this->ticket->asunto));

    $this->travel(31)->days();
    $this->get($url)->assertInertia(fn (Assert $p) => $p->component('portal/enlace-caducado'));
});

it('el proveedor programa la visita con fecha futura', function () {
    asignarPaco($this);
    $t = $this->ticket->fresh();

    $this->post(EnlacesProveedor::visita($t), ['fecha' => now()->subDay()->format('Y-m-d H:i')])->assertSessionHasErrors('fecha');
    $this->post(EnlacesProveedor::visita($t), ['fecha' => now()->addDays(2)->format('Y-m-d H:i')])->assertRedirect();

    expect($t->fresh()->estado)->toBe('visita_programada')
        ->and($t->fresh()->visita_programada_en)->not->toBeNull();
});

it('el proveedor marca la incidencia como resuelta con un comentario', function () {
    asignarPaco($this);
    $t = $this->ticket->fresh();

    $this->post(EnlacesProveedor::resolver($t), ['comentario' => ''])->assertSessionHasErrors('comentario');
    $this->post(EnlacesProveedor::resolver($t), ['comentario' => 'Cambiada la llave de paso'])->assertRedirect();

    $m = Mensaje::sole();
    expect($t->fresh()->estado)->toBe('resuelta')
        ->and($m->autor->is($this->fontanero))->toBeTrue()
        ->and($m->cuerpo)->toBe('Cambiada la llave de paso');
});

it('el enlace de un proveedor sustituido deja de funcionar', function () {
    asignarPaco($this);
    $url = EnlacesProveedor::ver($this->ticket->fresh());
    $nuevo = Proveedor::create(['empresa_id' => $this->empresa->id, 'nombre' => 'Otro', 'oficio' => 'Fontanero', 'email' => 'otro@x.test']);
    $nuevo->edificios()->attach($this->edificio);
    $this->actingAs($this->gestor)->post('/panel/tickets/3/proveedor', ['proveedor_id' => $nuevo->id]);

    auth()->guard('web')->logout();
    $this->get($url)->assertForbidden();
});

it('el detalle del panel muestra edificio, zona, afectados y proveedores disponibles', function () {
    $this->actingAs($this->gestor)->get('/panel/tickets/3')->assertInertia(fn (Assert $p) => $p
        ->where('ticket.edificio', 'Residencial Mar')
        ->where('ticket.zona', 'Portal')
        ->where('ticket.afectados', 0)
        ->has('ticket.proveedoresDisponibles', 1));
});

it('la bandeja filtra por edificio', function () {
    Ticket::factory()->create(['empresa_id' => $this->empresa->id, 'numero' => 9, 'edificio_id' => $this->otroEdificio->id]);

    $this->actingAs($this->gestor)->get('/panel?edificio_id='.$this->otroEdificio->id)->assertInertia(fn (Assert $p) => $p
        ->has('tickets.data', 1)
        ->where('tickets.data.0.numero', 9)
        ->has('opciones.edificios', 2));
});
