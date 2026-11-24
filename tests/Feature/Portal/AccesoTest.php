<?php

use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\Ticket;
use App\Notifications\EnlaceAcceso;
use App\Portal\EnlacesPortal;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Notification::fake();
    $this->empresa = Empresa::factory()->soporteIt()->create(['slug' => 'soporte-tic']);
    $this->cliente = Cliente::factory()->for($this->empresa)->create(['email' => 'ana@cole.es']);
});

it('pedir acceso envía el enlace solo si el email existe y responde igual', function () {
    $r1 = $this->post('/soporte-tic/acceder', ['email' => 'ANA@cole.es']);
    $r2 = $this->post('/soporte-tic/acceder', ['email' => 'nadie@cole.es']);

    $r1->assertRedirect('/soporte-tic/acceder')->assertSessionHas('status');
    $r2->assertRedirect('/soporte-tic/acceder')->assertSessionHas('status', session('status'));
    Notification::assertSentTo($this->cliente, EnlaceAcceso::class);
    Notification::assertCount(1);
});

it('el enlace de acceso inicia sesión y caduca a los 30 minutos', function () {
    $url = URL::temporarySignedRoute('portal.entrar', now()->addMinutes(30), ['empresa' => 'soporte-tic', 'cliente' => $this->cliente->id]);

    $this->travel(31)->minutes();
    $this->get($url)->assertInertia(fn (Assert $p) => $p->component('portal/enlace-caducado'));
    $this->assertGuest('cliente');

    $this->travelBack();
    $this->get(URL::temporarySignedRoute('portal.entrar', now()->addMinutes(30), ['empresa' => 'soporte-tic', 'cliente' => $this->cliente->id]))
        ->assertRedirect('/soporte-tic/mis-tickets');
    $this->assertAuthenticatedAs($this->cliente, 'cliente');
});

it('el enlace de una notificación abre el ticket con sesión', function () {
    $t = Ticket::factory()->create(['empresa_id' => $this->empresa->id, 'cliente_id' => $this->cliente->id, 'numero' => 7]);

    $this->get(EnlacesPortal::ticket($t))->assertRedirect('/soporte-tic/tickets/7');
    $this->assertAuthenticatedAs($this->cliente, 'cliente');
});

it('mis tickets muestra solo los del cliente', function () {
    Ticket::factory()->count(2)->create(['empresa_id' => $this->empresa->id, 'cliente_id' => $this->cliente->id]);
    Ticket::factory()->create(['empresa_id' => $this->empresa->id]);

    $this->actingAs($this->cliente, 'cliente')->get('/soporte-tic/mis-tickets')
        ->assertInertia(fn (Assert $p) => $p->component('portal/mis-tickets')->has('tickets', 2));
});

it('un cliente no ve el ticket de otro cliente', function () {
    $ajeno = Ticket::factory()->create(['empresa_id' => $this->empresa->id, 'numero' => 99]);

    $this->actingAs($this->cliente, 'cliente')->get('/soporte-tic/tickets/99')->assertNotFound();
});

it('el cliente ve su ticket', function () {
    Ticket::factory()->create(['empresa_id' => $this->empresa->id, 'cliente_id' => $this->cliente->id, 'numero' => 3, 'asunto' => 'Proyector']);

    $this->actingAs($this->cliente, 'cliente')->get('/soporte-tic/tickets/3')
        ->assertInertia(fn (Assert $p) => $p->component('portal/ticket')->where('ticket.asunto', 'Proyector'));
});

it('la sesión de un cliente no vale en otra empresa', function () {
    Empresa::factory()->create(['slug' => 'otra']);

    $this->actingAs($this->cliente, 'cliente')->get('/otra/mis-tickets')->assertRedirect('/otra/acceder');
});

it('mis tickets sin sesión redirige a acceder', function () {
    $this->get('/soporte-tic/mis-tickets')->assertRedirect('/soporte-tic/acceder');
});

it('puede fijar contraseña y entrar con ella', function () {
    $this->actingAs($this->cliente, 'cliente')
        ->post('/soporte-tic/cuenta/password', ['password' => 'secreto123', 'password_confirmation' => 'secreto123'])
        ->assertSessionHasNoErrors();
    $this->post('/soporte-tic/salir');
    $this->assertGuest('cliente');

    $this->post('/soporte-tic/login', ['email' => 'ana@cole.es', 'password' => 'mala'])->assertSessionHasErrors('email');
    $this->post('/soporte-tic/login', ['email' => 'ana@cole.es', 'password' => 'secreto123'])->assertRedirect('/soporte-tic/mis-tickets');
    $this->assertAuthenticatedAs($this->cliente, 'cliente');
});
