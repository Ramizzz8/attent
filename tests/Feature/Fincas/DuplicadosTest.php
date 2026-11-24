<?php

require_once __DIR__.'/FincasHelpers.php';

use App\Enums\Prioridad;
use App\Fincas\BuscarDuplicado;
use App\Fincas\SumarAfectado;
use App\Models\Cliente;
use App\Models\Ticket;
use App\Notifications\ConfirmarAfectado;
use App\Notifications\EstadoCambiado;
use App\Tickets\CambiarEstado;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Notification::fake();
    montarFincas($this);
});

function incidencia($test, array $attrs = []): Ticket
{
    return Ticket::factory()->create([
        'empresa_id' => $test->empresa->id, 'estado' => 'reportada', 'categoria' => 'averia',
        'edificio_id' => $test->edificio->id, 'zona_id' => $test->ascensor->id, 'prioridad' => Prioridad::Media, ...$attrs,
    ]);
}

it('el formulario del portal recibe los edificios con sus zonas', function () {
    $this->get('/fincas-levante/nueva')->assertInertia(fn (Assert $p) => $p
        ->has('edificios', 2)
        ->where('edificios.0.nombre', 'Residencial Mar')
        ->has('edificios.0.zonas', 2));
});

it('el enlace de un edificio lo deja preseleccionado', function () {
    $this->get('/fincas-levante/edificio/torre-sol')->assertInertia(fn (Assert $p) => $p
        ->component('portal/nueva')
        ->where('valoresIniciales.edificio_id', $this->otroEdificio->id));
});

it('no acepta una zona de otro edificio', function () {
    $this->post('/fincas-levante/tickets', altaFincas(['zona_id' => $this->garajeSol->id]))->assertSessionHasErrors('zona_id');
});

it('una zona marcada como prioritaria empieza en alta', function () {
    $this->post('/fincas-levante/tickets', altaFincas(['zona_id' => $this->ascensor->id, 'categoria' => 'averia']));

    expect(Ticket::withoutGlobalScopes()->sole()->prioridad)->toBe(Prioridad::Alta);
});

it('detecta una incidencia abierta igual en el mismo edificio', function () {
    $t = incidencia($this);

    expect(app(BuscarDuplicado::class)->ejecutar($this->edificio->id, $this->ascensor->id, 'averia')?->id)->toBe($t->id);
    $this->getJson("/fincas-levante/duplicados?edificio_id={$this->edificio->id}&zona_id={$this->ascensor->id}&categoria=averia")
        ->assertOk()->assertJsonPath('ticket.numero', $t->numero);
});

it('no detecta duplicados de otro edificio, otra categoría o ya resueltos', function () {
    incidencia($this, ['estado' => 'resuelta']);
    incidencia($this, ['categoria' => 'limpieza']);
    incidencia($this, ['edificio_id' => $this->otroEdificio->id, 'zona_id' => $this->garajeSol->id]);

    expect(app(BuscarDuplicado::class)->ejecutar($this->edificio->id, $this->ascensor->id, 'averia'))->toBeNull();
});

it('sumarse dos veces cuenta una y el creador no se suma', function () {
    $t = incidencia($this);
    $vecino = Cliente::factory()->for($this->empresa)->create();
    $sumar = app(SumarAfectado::class);

    $sumar->ejecutar($t, $vecino);
    $sumar->ejecutar($t, $vecino);
    $sumar->ejecutar($t, $t->cliente);

    expect($t->afectados()->count())->toBe(1);
});

it('la prioridad sube con 3 afectados y llega a urgente con 6, sin bajar nunca', function () {
    $t = incidencia($this, ['zona_id' => $this->portal->id, 'categoria' => 'limpieza']);
    $sumar = app(SumarAfectado::class);
    $vecinos = Cliente::factory()->for($this->empresa)->count(5)->create();

    $sumar->ejecutar($t, $vecinos[0]);
    expect($t->fresh()->prioridad)->toBe(Prioridad::Media);
    $sumar->ejecutar($t, $vecinos[1]); // creador + 2 = 3
    expect($t->fresh()->prioridad)->toBe(Prioridad::Alta)
        ->and($t->eventos()->where('tipo', 'prioridad')->count())->toBe(1);
    $sumar->ejecutar($t, $vecinos[2]);
    $sumar->ejecutar($t, $vecinos[3]);
    $sumar->ejecutar($t, $vecinos[4]); // 6
    expect($t->fresh()->prioridad)->toBe(Prioridad::Urgente);
});

it('un vecino con sesión se suma desde el portal y luego ve la incidencia', function () {
    $t = incidencia($this, ['numero' => 4]);
    $vecino = Cliente::factory()->for($this->empresa)->create();

    $this->actingAs($vecino, 'cliente')->post('/fincas-levante/tickets/4/afectado')->assertRedirect('/fincas-levante/tickets/4');

    $this->actingAs($vecino, 'cliente')->get('/fincas-levante/tickets/4')->assertOk();
    $this->actingAs($vecino, 'cliente')->get('/fincas-levante/mis-tickets')->assertInertia(fn (Assert $p) => $p->has('tickets', 1));
});

it('un vecino sin sesión confirma por email antes de sumarse', function () {
    $t = incidencia($this, ['numero' => 4]);

    $this->post('/fincas-levante/tickets/4/afectado', ['nombre' => 'Luis', 'email' => 'luis@x.es'])->assertRedirect('/fincas-levante/pendiente');
    $luis = Cliente::where('email', 'luis@x.es')->sole();
    expect($t->afectados()->count())->toBe(0);
    Notification::assertSentTo($luis, ConfirmarAfectado::class);

    $url = URL::temporarySignedRoute('portal.afectado.confirmar', now()->addDay(), ['empresa' => 'fincas-levante', 'ticket' => $t->id, 'cliente' => $luis->id]);
    $this->get($url)->assertRedirect('/fincas-levante/tickets/4');

    expect($t->afectados()->count())->toBe(1);
    $this->assertAuthenticatedAs($luis, 'cliente');
});

it('los afectados reciben los avisos de cambio de estado', function () {
    $t = incidencia($this);
    $vecino = Cliente::factory()->for($this->empresa)->create();
    app(SumarAfectado::class)->ejecutar($t, $vecino);

    app(CambiarEstado::class)->ejecutar($t, 'en_revision', null);

    Notification::assertSentTo([$t->cliente, $vecino], EstadoCambiado::class);
});
