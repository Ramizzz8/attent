<?php

use App\Models\Cliente;
use App\Models\Empresa;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Notification::fake();
    $this->empresa = Empresa::factory()->soporteIt()->create(['slug' => 'soporte-tic']);
});

function altaDemo(): array
{
    return ['nombre' => 'Visitante', 'email' => 'visita@x.es', 'asunto' => 'Prueba', 'descripcion' => 'Probando', 'categoria' => 'software', 'impacto' => 'individual'];
}

it('en modo demo el enlace de confirmación aparece en pantalla', function () {
    config(['atiende.demo' => true]);

    $this->post('/soporte-tic/tickets', altaDemo())->assertSessionHas('enlaceDemo');
    $this->get('/soporte-tic/pendiente')->assertInertia(fn (Assert $p) => $p->where('enlaceDemo', fn ($url) => str_contains($url, '/soporte-tic/confirmar/')));
});

it('fuera del modo demo el enlace nunca se muestra', function () {
    config(['atiende.demo' => false]);

    $this->post('/soporte-tic/tickets', altaDemo())->assertSessionMissing('enlaceDemo');
    $this->get('/soporte-tic/pendiente')->assertInertia(fn (Assert $p) => $p->where('enlaceDemo', null));
});

it('en modo demo pedir acceso muestra el enlace si el cliente existe', function () {
    config(['atiende.demo' => true]);
    Cliente::factory()->for($this->empresa)->create(['email' => 'ana@cole.es']);

    $this->post('/soporte-tic/acceder', ['email' => 'ana@cole.es'])->assertSessionHas('enlaceDemo');
    $this->post('/soporte-tic/acceder', ['email' => 'nadie@cole.es'])->assertSessionMissing('enlaceDemo');
});

it('en modo demo la pantalla de acceso del panel muestra las cuentas de prueba', function () {
    config(['atiende.demo' => true]);

    $this->get('/login')->assertInertia(fn (Assert $p) => $p->has('cuentasDemo', 3));
});

it('fuera del modo demo no se muestran cuentas de prueba', function () {
    config(['atiende.demo' => false]);

    $this->get('/login')->assertInertia(fn (Assert $p) => $p->where('cuentasDemo', null));
});

it('detrás del proxy HTTPS (Caddy) los enlaces que se generan son https', function () {
    config(['atiende.demo' => true]);

    $this->withServerVariables(['REMOTE_ADDR' => '172.18.0.5', 'HTTP_HOST' => 'atiende.test'])
        ->withHeaders(['X-Forwarded-Proto' => 'https', 'X-Forwarded-Host' => 'atiende.test', 'X-Forwarded-Port' => '443'])
        ->post('http://atiende.test/soporte-tic/tickets', altaDemo());

    expect(session('enlaceDemo'))->toStartWith('https://atiende.test/soporte-tic/confirmar/');
});

it('detrás del proxy HTTPS (Caddy) los enlaces firmados siguen siendo válidos', function () {
    $cliente = Cliente::factory()->for($this->empresa)->create();
    URL::forceRootUrl('https://atiende.test');
    URL::forceScheme('https');
    $enlace = App\Portal\EnlacesPortal::entrar($cliente);
    URL::forceRootUrl(null);
    URL::forceScheme(null);
    expect($enlace)->toStartWith('https://atiende.test/');

    // Caddy termina el TLS y reenvía la petición por HTTP indicando el protocolo original.
    $this->withServerVariables(['REMOTE_ADDR' => '172.18.0.5', 'HTTP_HOST' => 'atiende.test'])
        ->withHeaders(['X-Forwarded-Proto' => 'https', 'X-Forwarded-Host' => 'atiende.test', 'X-Forwarded-Port' => '443'])
        ->get(str_replace('https://', 'http://', $enlace))
        ->assertRedirect('/soporte-tic/mis-tickets');
});
