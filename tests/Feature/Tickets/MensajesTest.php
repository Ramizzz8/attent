<?php

use App\Models\Adjunto;
use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\Mensaje;
use App\Models\Ticket;
use App\Models\User;
use App\Tickets\EnviarMensaje;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Storage::fake('local');
    $this->empresa = Empresa::factory()->soporteIt()->create(['slug' => 'soporte-tic']);
    $this->cliente = Cliente::factory()->for($this->empresa)->create();
    $this->empleado = User::factory()->for($this->empresa)->create();
    $this->ticket = Ticket::factory()->create([
        'empresa_id' => $this->empresa->id, 'cliente_id' => $this->cliente->id, 'numero' => 5, 'estado' => 'en_curso',
    ]);
});

it('el cliente responde desde el portal con un adjunto', function () {
    $this->actingAs($this->cliente, 'cliente')
        ->post('/soporte-tic/tickets/5/mensajes', [
            'cuerpo' => 'Adjunto la captura',
            'adjuntos' => [UploadedFile::fake()->image('captura.png')],
        ])->assertRedirect('/soporte-tic/tickets/5');

    $m = Mensaje::sole();
    expect($m->cuerpo)->toBe('Adjunto la captura')
        ->and($m->interno)->toBeFalse()
        ->and($m->autor->is($this->cliente))->toBeTrue()
        ->and($m->adjuntos)->toHaveCount(1);
    Storage::disk('local')->assertExists($m->adjuntos->first()->ruta);
});

it('el cliente no puede escribir notas internas aunque lo intente', function () {
    $this->actingAs($this->cliente, 'cliente')
        ->post('/soporte-tic/tickets/5/mensajes', ['cuerpo' => 'Hola', 'interno' => true]);

    expect(Mensaje::sole()->interno)->toBeFalse();
});

it('el cliente no ve las notas internas', function () {
    app(EnviarMensaje::class)->ejecutar($this->ticket, $this->empleado, 'Respuesta pública');
    app(EnviarMensaje::class)->ejecutar($this->ticket, $this->empleado, 'Ojo, cliente pesado', interno: true);

    $this->actingAs($this->cliente, 'cliente')->get('/soporte-tic/tickets/5')
        ->assertInertia(fn (Assert $p) => $p
            ->has('ticket.mensajes', 1)
            ->where('ticket.mensajes.0.cuerpo', 'Respuesta pública'));
});

it('el cliente no descarga adjuntos de notas internas', function () {
    $m = app(EnviarMensaje::class)->ejecutar($this->ticket, $this->empleado, 'Nota', [UploadedFile::fake()->create('interno.pdf', 10, 'application/pdf')], interno: true);

    $this->actingAs($this->cliente, 'cliente')->get('/soporte-tic/adjuntos/'.$m->adjuntos->first()->id)->assertNotFound();
});

it('el cliente descarga los adjuntos públicos de su ticket', function () {
    $m = app(EnviarMensaje::class)->ejecutar($this->ticket, $this->empleado, 'Te envío el informe', [UploadedFile::fake()->create('informe.pdf', 10, 'application/pdf')]);

    $this->actingAs($this->cliente, 'cliente')->get('/soporte-tic/adjuntos/'.$m->adjuntos->first()->id)->assertOk();
});

it('otro cliente no descarga adjuntos ajenos', function () {
    $m = app(EnviarMensaje::class)->ejecutar($this->ticket, $this->empleado, 'Informe', [UploadedFile::fake()->create('informe.pdf', 10, 'application/pdf')]);
    $otro = Cliente::factory()->for($this->empresa)->create();

    $this->actingAs($otro, 'cliente')->get('/soporte-tic/adjuntos/'.$m->adjuntos->first()->id)->assertNotFound();
});

it('un empleado descarga adjuntos de su empresa pero no de otra', function () {
    $m = app(EnviarMensaje::class)->ejecutar($this->ticket, $this->cliente, 'Foto', [UploadedFile::fake()->image('foto.jpg')]);
    $ajeno = User::factory()->create();

    $this->actingAs($this->empleado)->get('/panel/adjuntos/'.$m->adjuntos->first()->id)->assertOk();
    $this->actingAs($ajeno)->get('/panel/adjuntos/'.$m->adjuntos->first()->id)->assertNotFound();
});

it('el empleado escribe una nota interna desde el panel', function () {
    $this->actingAs($this->empleado)
        ->post('/panel/tickets/5/mensajes', ['cuerpo' => 'Revisar mañana', 'interno' => true])
        ->assertRedirect();

    expect(Mensaje::sole()->interno)->toBeTrue();
});

it('la respuesta del cliente reanuda un ticket pendiente del usuario', function () {
    $this->ticket->update(['estado' => 'pendiente_usuario', 'sla_pausado_en' => now()]);

    app(EnviarMensaje::class)->ejecutar($this->ticket, $this->cliente, 'Ya lo he probado');

    expect($this->ticket->fresh()->estado)->toBe('en_curso')
        ->and($this->ticket->fresh()->sla_pausado_en)->toBeNull();
});

it('registra la primera respuesta pública del equipo una sola vez', function () {
    $this->freezeSecond();
    $enviar = app(EnviarMensaje::class);

    $enviar->ejecutar($this->ticket, $this->empleado, 'Nota', interno: true);
    expect($this->ticket->fresh()->primera_respuesta_en)->toBeNull();

    $enviar->ejecutar($this->ticket, $this->empleado, 'Lo miro');
    $primera = $this->ticket->fresh()->primera_respuesta_en;
    $this->travel(1)->hours();
    $enviar->ejecutar($this->ticket, $this->empleado, 'Ya está');

    expect($primera->equalTo(now()->subHour()))->toBeTrue()
        ->and($this->ticket->fresh()->primera_respuesta_en->equalTo($primera))->toBeTrue();
});

it('rechaza archivos no permitidos o demasiado grandes', function (UploadedFile $archivo) {
    $this->actingAs($this->cliente, 'cliente')
        ->post('/soporte-tic/tickets/5/mensajes', ['cuerpo' => 'Mira', 'adjuntos' => [$archivo]])
        ->assertSessionHasErrors('adjuntos.0');

    expect(Mensaje::count())->toBe(0)->and(Adjunto::count())->toBe(0);
})->with([
    'demasiado grande' => fn () => UploadedFile::fake()->create('grande.pdf', 6 * 1024, 'application/pdf'),
    'ejecutable' => fn () => UploadedFile::fake()->create('virus.exe', 10, 'application/x-msdownload'),
    'svg' => fn () => UploadedFile::fake()->createWithContent('dibujo.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'),
]);

it('rechaza más de cinco archivos', function () {
    $this->actingAs($this->cliente, 'cliente')
        ->post('/soporte-tic/tickets/5/mensajes', ['cuerpo' => 'Mira', 'adjuntos' => array_map(fn ($i) => UploadedFile::fake()->image("f{$i}.png"), range(1, 6))])
        ->assertSessionHasErrors('adjuntos');
});

it('las fotos del alta se guardan como adjuntos del ticket', function () {
    $this->post('/soporte-tic/tickets', [
        'nombre' => 'Ana', 'email' => 'ana@x.es', 'asunto' => 'Pantalla rota', 'descripcion' => 'Se ve negra',
        'categoria' => 'hardware', 'impacto' => 'individual',
        'adjuntos' => [UploadedFile::fake()->image('pantalla.jpg')],
    ])->assertRedirect('/soporte-tic/pendiente');

    $nuevo = Ticket::withoutGlobalScopes()->latest('id')->first();
    expect(Adjunto::where('ticket_id', $nuevo->id)->whereNull('mensaje_id')->count())->toBe(1);
});

it('no se puede escribir en el ticket de otro cliente', function () {
    $otro = Cliente::factory()->for($this->empresa)->create();

    $this->actingAs($otro, 'cliente')->post('/soporte-tic/tickets/5/mensajes', ['cuerpo' => 'Hola'])->assertNotFound();
});
