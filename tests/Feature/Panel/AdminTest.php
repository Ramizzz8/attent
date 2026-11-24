<?php

use App\Enums\Prioridad;
use App\Enums\Rol;
use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\User;
use App\Tickets\CrearTicket;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->empresa = Empresa::factory()->soporteIt()->create();
    $this->admin = User::factory()->for($this->empresa)->admin()->create();
});

it('un empleado no entra en empleados ni ajustes', function () {
    $empleado = User::factory()->for($this->empresa)->create();

    $this->actingAs($empleado)->get('/panel/empleados')->assertForbidden();
    $this->actingAs($empleado)->get('/panel/ajustes')->assertForbidden();
});

it('lista solo los empleados de su empresa', function () {
    User::factory()->for($this->empresa)->create();
    User::factory()->create();

    $this->actingAs($this->admin)->get('/panel/empleados')->assertInertia(fn (Assert $p) => $p
        ->component('panel/admin/empleados')
        ->has('empleados', 2));
});

it('da de alta un empleado y le envía el enlace para crear contraseña', function () {
    Notification::fake();

    $this->actingAs($this->admin)->post('/panel/empleados', ['name' => 'Nuevo Técnico', 'email' => 'tec@cole.test', 'rol' => 'empleado'])
        ->assertSessionHasNoErrors();

    $u = User::where('email', 'tec@cole.test')->sole();
    expect($u->empresa_id)->toBe($this->empresa->id)->and($u->rol)->toBe(Rol::Empleado);
    Notification::assertSentTo($u, ResetPassword::class);
});

it('no se puede quitar el último administrador', function () {
    $this->actingAs($this->admin)->patch("/panel/empleados/{$this->admin->id}", ['rol' => 'empleado'])->assertSessionHasErrors('rol');
    $this->actingAs($this->admin)->delete("/panel/empleados/{$this->admin->id}")->assertSessionHasErrors('empleado');

    expect($this->admin->fresh()->rol)->toBe(Rol::Admin);
});

it('cambia el rol y da de baja a otros empleados', function () {
    $u = User::factory()->for($this->empresa)->create();

    $this->actingAs($this->admin)->patch("/panel/empleados/{$u->id}", ['rol' => 'admin']);
    expect($u->fresh()->rol)->toBe(Rol::Admin);

    $this->actingAs($this->admin)->delete("/panel/empleados/{$u->id}");
    expect(User::find($u->id))->toBeNull();
});

it('no gestiona empleados de otra empresa', function () {
    $ajeno = User::factory()->create();

    $this->actingAs($this->admin)->patch("/panel/empleados/{$ajeno->id}", ['rol' => 'admin'])->assertNotFound();
    $this->actingAs($this->admin)->delete("/panel/empleados/{$ajeno->id}")->assertNotFound();
});

it('guarda los ajustes del portal con logo', function () {
    Storage::fake('public');

    $this->actingAs($this->admin)->post('/panel/ajustes', [
        'nombre' => 'IES Ribera', 'color_primario' => '#0f766e', 'texto_portal' => 'Soporte TIC del instituto',
        'logo' => UploadedFile::fake()->image('logo.png', 200, 200),
    ])->assertSessionHasNoErrors();

    $e = $this->empresa->fresh();
    expect($e->nombre)->toBe('IES Ribera')
        ->and($e->color_primario)->toBe('#0f766e')
        ->and($e->ajustes['texto_portal'])->toBe('Soporte TIC del instituto');
    Storage::disk('public')->assertExists($e->logo_path);
});

it('rechaza un color no válido', function () {
    $this->actingAs($this->admin)->post('/panel/ajustes', ['nombre' => 'X', 'color_primario' => 'rojo'])->assertSessionHasErrors('color_primario');
});

it('el SLA personalizado se aplica a los tickets nuevos', function () {
    $this->freezeSecond();
    $this->actingAs($this->admin)->post('/panel/ajustes', [
        'nombre' => $this->empresa->nombre, 'color_primario' => '#2563eb',
        'sla_minutos' => ['baja' => 5000, 'media' => 100, 'alta' => 60, 'urgente' => 30],
    ])->assertSessionHasNoErrors();

    $e = $this->empresa->fresh();
    $t = app(CrearTicket::class)->ejecutar($e, Cliente::factory()->for($e)->create(),
        ['asunto' => 'A', 'descripcion' => 'B', 'categoria' => 'software', 'impacto' => 'departamento'], true);

    expect($t->prioridad)->toBe(Prioridad::Media)
        ->and($t->sla_vence_en->equalTo(now()->addMinutes(100)))->toBeTrue();
});

it('rechaza minutos de SLA fuera de rango', function () {
    $this->actingAs($this->admin)->post('/panel/ajustes', [
        'nombre' => 'X', 'color_primario' => '#2563eb', 'sla_minutos' => ['media' => 5],
    ])->assertSessionHasErrors('sla_minutos.media');
});
