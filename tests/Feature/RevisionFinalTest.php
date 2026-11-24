<?php

/*
| Correcciones de la revisión final: cada test reproduce un hallazgo.
*/

use App\Fincas\SumarAfectado;
use App\Models\Cliente;
use App\Models\Edificio;
use App\Models\Empresa;
use App\Models\Mensaje;
use App\Models\Proveedor;
use App\Models\Ticket;
use App\Models\User;
use App\Models\Zona;
use App\Fincas\EnlacesProveedor;
use App\Notifications\NuevoMensaje;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

it('"me afecta" no sirve fuera de fincas: un cliente no puede colarse en el ticket de otro', function () {
    $taller = Empresa::factory()->taller()->create(['slug' => 'taller-x']);
    $ajeno = Ticket::factory()->create(['empresa_id' => $taller->id, 'numero' => 1]);
    $intruso = Cliente::factory()->for($taller)->create();

    $this->actingAs($intruso, 'cliente')->post('/taller-x/tickets/1/afectado')->assertNotFound();
    $this->actingAs($intruso, 'cliente')->get('/taller-x/tickets/1')->assertNotFound();
    expect($ajeno->afectados()->count())->toBe(0);
});

it('"me afecta" no sirve en incidencias de fincas ya resueltas, tampoco con el enlace de confirmación', function () {
    $fincas = Empresa::factory()->fincas()->create(['slug' => 'fincas-x']);
    $t = Ticket::factory()->create(['empresa_id' => $fincas->id, 'numero' => 2, 'estado' => 'resuelta']);
    $vecino = Cliente::factory()->for($fincas)->create();

    $this->actingAs($vecino, 'cliente')->post('/fincas-x/tickets/2/afectado')->assertNotFound();
    $url = URL::temporarySignedRoute('portal.afectado.confirmar', now()->addDay(), ['empresa' => 'fincas-x', 'ticket' => $t->id, 'cliente' => $vecino->id]);
    $this->get($url)->assertNotFound();

    expect($t->afectados()->count())->toBe(0);
});

it('sumar un afectado que ya está no rompe aunque la fila ya exista', function () {
    $fincas = Empresa::factory()->fincas()->create();
    $t = Ticket::factory()->create(['empresa_id' => $fincas->id, 'estado' => 'reportada']);
    $vecino = Cliente::factory()->for($fincas)->create();
    // Simula la carrera de un doble clic: la fila ya existe cuando llega la segunda petición.
    Illuminate\Support\Facades\DB::table('ticket_afectados')->insert(['ticket_id' => $t->id, 'cliente_id' => $vecino->id]);

    app(SumarAfectado::class)->ejecutar($t, $vecino);

    expect($t->afectados()->count())->toBe(1);
});

it('un archivo rechazado por el límite de PHP da un mensaje en español sobre el tamaño', function () {
    $e = Empresa::factory()->soporteIt()->create(['slug' => 'it-x']);
    $c = Cliente::factory()->for($e)->create();
    Ticket::factory()->create(['empresa_id' => $e->id, 'cliente_id' => $c->id, 'numero' => 3]);
    $ruta = tempnam(sys_get_temp_dir(), 'foto');
    file_put_contents($ruta, 'x');
    $demasiado = new UploadedFile($ruta, 'foto.jpg', 'image/jpeg', UPLOAD_ERR_INI_SIZE, true);

    $this->actingAs($c, 'cliente')->post('/it-x/tickets/3/mensajes', ['cuerpo' => 'Mira', 'adjuntos' => [$demasiado]])
        ->assertSessionHasErrors(['adjuntos.0' => 'El archivo es demasiado grande. El máximo son 5 MB por archivo.']);
});

it('un envío que supera el límite total del servidor vuelve atrás con un error, no un 413', function () {
    $e = Empresa::factory()->soporteIt()->create(['slug' => 'it-y']);
    $c = Cliente::factory()->for($e)->create();
    Ticket::factory()->create(['empresa_id' => $e->id, 'cliente_id' => $c->id, 'numero' => 3]);

    $this->actingAs($c, 'cliente')
        ->from('/it-y/tickets/3')
        ->call('POST', '/it-y/tickets/3/mensajes', ['cuerpo' => 'x'], [], [], ['CONTENT_LENGTH' => 1024 * 1024 * 1024])
        ->assertRedirect('/it-y/tickets/3')
        ->assertSessionHasErrors('adjuntos');
});

it('el último administrador no puede borrar su cuenta desde los ajustes del perfil', function () {
    $e = Empresa::factory()->create();
    $admin = User::factory()->for($e)->admin()->create();

    $this->actingAs($admin)->delete('/settings/profile', ['password' => 'password'])->assertSessionHasErrors('password');

    expect(User::find($admin->id))->not->toBeNull();
});

it('si el proveedor envía "resuelta" dos veces, el cliente recibe un solo mensaje', function () {
    Notification::fake();
    $e = Empresa::factory()->fincas()->create();
    $ed = Edificio::create(['empresa_id' => $e->id, 'nombre' => 'A', 'direccion' => 'B', 'slug' => 'a']);
    $z = Zona::create(['edificio_id' => $ed->id, 'nombre' => 'Portal']);
    $p = Proveedor::create(['empresa_id' => $e->id, 'nombre' => 'P', 'oficio' => 'O', 'email' => 'p@x.test']);
    $p->edificios()->attach($ed);
    $t = Ticket::factory()->create(['empresa_id' => $e->id, 'estado' => 'asignada_proveedor', 'edificio_id' => $ed->id, 'zona_id' => $z->id, 'proveedor_id' => $p->id]);
    $url = EnlacesProveedor::resolver($t);

    $this->post($url, ['comentario' => 'Arreglado']);
    $this->post($url, ['comentario' => 'Arreglado'])->assertSessionHasErrors('comentario');

    expect(Mensaje::count())->toBe(1);
    Notification::assertSentToTimes($t->cliente, NuevoMensaje::class, 1);
});
