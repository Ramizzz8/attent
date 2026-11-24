<?php

use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\Ticket;
use App\Notifications\ConfirmarTicket;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Notification::fake();
    $this->empresa = Empresa::factory()->taller()->create(['nombre' => 'Talleres Ruiz', 'slug' => 'talleres-ruiz']);
});

function altaTaller(array $extra = []): array
{
    return [
        'nombre' => 'Lucía Pérez', 'email' => 'Lucia@Ejemplo.es', 'telefono' => '600111222',
        'asunto' => 'Ruido al frenar', 'descripcion' => 'Chirría la rueda delantera', 'categoria' => 'mecanica',
        'datos' => ['matricula' => '1234 ABC', 'marca' => 'Seat', 'modelo' => 'Ibiza', 'km' => 120000],
        ...$extra,
    ];
}

it('muestra el portal con la marca de la empresa', function () {
    $this->get('/talleres-ruiz')->assertInertia(fn (Assert $p) => $p
        ->component('portal/inicio')
        ->where('portal.empresa.nombre', 'Talleres Ruiz')
        ->where('portal.empresa.sector', 'taller'));
});

it('un slug inexistente da 404', function () {
    $this->get('/no-existe')->assertNotFound();
});

it('las rutas reservadas no se confunden con empresas', function () {
    $this->get('/login')->assertOk();
});

it('el formulario incluye las categorías del sector', function () {
    $this->get('/talleres-ruiz/nueva')->assertInertia(fn (Assert $p) => $p
        ->component('portal/nueva')
        ->has('categorias', 6));
});

it('un invitado crea un ticket sin verificar y recibe el email de confirmación', function () {
    $this->post('/talleres-ruiz/tickets', altaTaller())->assertRedirect('/talleres-ruiz/pendiente');

    $ticket = Ticket::withoutGlobalScopes()->sole();
    $cliente = Cliente::withoutGlobalScopes()->sole();
    expect($ticket->verificado_en)->toBeNull()
        ->and($cliente->email)->toBe('lucia@ejemplo.es')
        ->and($cliente->empresa_id)->toBe($this->empresa->id)
        ->and($ticket->datos['matricula'])->toBe('1234 ABC');
    Notification::assertSentTo($cliente, ConfirmarTicket::class);
    $this->assertGuest('cliente');
});

it('reutiliza el cliente si el email ya existe', function () {
    Cliente::factory()->for($this->empresa)->create(['email' => 'lucia@ejemplo.es']);

    $this->post('/talleres-ruiz/tickets', altaTaller());

    expect(Cliente::withoutGlobalScopes()->count())->toBe(1);
});

it('valida los campos comunes y los del sector', function () {
    $this->post('/talleres-ruiz/tickets', altaTaller(['asunto' => '', 'categoria' => 'cohete', 'datos' => []]))
        ->assertSessionHasErrors(['asunto', 'categoria', 'datos.matricula']);

    expect(Ticket::withoutGlobalScopes()->count())->toBe(0);
});

it('confirmar con un enlace válido verifica e inicia sesión', function () {
    $this->post('/talleres-ruiz/tickets', altaTaller());
    $ticket = Ticket::withoutGlobalScopes()->sole();
    $url = URL::temporarySignedRoute('portal.confirmar', now()->addDay(), ['empresa' => 'talleres-ruiz', 'ticket' => $ticket->id]);

    $this->get($url)->assertRedirect('/talleres-ruiz/tickets/1');

    expect($ticket->fresh()->verificado_en)->not->toBeNull()
        ->and($ticket->fresh()->sla_vence_en)->not->toBeNull();
    $this->assertAuthenticatedAs($ticket->cliente, 'cliente');
});

it('un enlace caducado muestra una página amable', function () {
    $this->post('/talleres-ruiz/tickets', altaTaller());
    $ticket = Ticket::withoutGlobalScopes()->sole();
    $url = URL::temporarySignedRoute('portal.confirmar', now()->addDay(), ['empresa' => 'talleres-ruiz', 'ticket' => $ticket->id]);

    $this->travel(25)->hours();

    $this->get($url)->assertOk()->assertInertia(fn (Assert $p) => $p
        ->component('portal/enlace-caducado')
        ->where('portal.empresa.slug', 'talleres-ruiz'));
    expect($ticket->fresh()->verificado_en)->toBeNull();
});

it('un enlace de una empresa no sirve en otra', function () {
    Empresa::factory()->taller()->create(['slug' => 'otro-taller']);
    $this->post('/talleres-ruiz/tickets', altaTaller());
    $ticket = Ticket::withoutGlobalScopes()->sole();
    $url = URL::temporarySignedRoute('portal.confirmar', now()->addDay(), ['empresa' => 'talleres-ruiz', 'ticket' => $ticket->id]);

    $this->get(str_replace('/talleres-ruiz/', '/otro-taller/', $url))
        ->assertInertia(fn (Assert $p) => $p->component('portal/enlace-caducado'));
});

it('un cliente con sesión crea el ticket ya verificado', function () {
    $cliente = Cliente::factory()->for($this->empresa)->create();

    $this->actingAs($cliente, 'cliente')
        ->post('/talleres-ruiz/tickets', altaTaller(['nombre' => null, 'email' => null]))
        ->assertRedirect('/talleres-ruiz/tickets/1');

    expect(Ticket::withoutGlobalScopes()->sole()->verificado_en)->not->toBeNull();
    Notification::assertNothingSent();
});
