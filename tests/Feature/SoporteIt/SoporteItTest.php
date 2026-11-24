<?php

use App\Enums\Prioridad;
use App\Models\Empresa;
use App\Models\Equipo;
use App\Models\Ticket;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->empresa = Empresa::factory()->soporteIt()->create(['slug' => 'soporte-tic']);
    $this->tecnico = User::factory()->for($this->empresa)->create();
    $this->admin = User::factory()->for($this->empresa)->admin()->create();
    $this->proyector = Equipo::create(['empresa_id' => $this->empresa->id, 'codigo' => 'PRY-012', 'tipo' => 'proyector', 'ubicacion' => 'Aula 12']);
});

function altaIt(array $extra = []): array
{
    return ['nombre' => 'Profe', 'email' => 'profe@cole.es', 'asunto' => 'No proyecta', 'descripcion' => 'Pantalla azul',
        'categoria' => 'hardware', 'impacto' => 'departamento', ...$extra];
}

it('el alta por código enlaza el equipo', function () {
    $this->post('/soporte-tic/tickets', altaIt(['equipo_codigo' => 'pry-012']))->assertSessionHasNoErrors();

    expect(Ticket::withoutGlobalScopes()->sole()->equipo_id)->toBe($this->proyector->id);
});

it('un código de equipo inexistente da error', function () {
    $this->post('/soporte-tic/tickets', altaIt(['equipo_codigo' => 'NOEXISTE']))->assertSessionHasErrors('equipo_codigo');
});

it('el formulario recibe los equipos y preselecciona el del enlace', function () {
    $this->get('/soporte-tic/nueva?equipo=PRY-012')->assertInertia(fn (Assert $p) => $p
        ->has('equipos', 1)
        ->where('valoresIniciales.equipo_codigo', 'PRY-012'));
});

it('el triaje recalcula prioridad y SLA de resolución, no la primera respuesta', function () {
    $this->freezeSecond();
    $t = Ticket::factory()->create([
        'empresa_id' => $this->empresa->id, 'numero' => 2, 'estado' => 'asignado', 'impacto' => 'todos', 'urgencia' => 'media',
        'prioridad' => Prioridad::Alta, 'verificado_en' => now(), 'sla_vence_en' => now()->addMinutes(480),
        'primera_respuesta_vence_en' => now()->addMinutes(60),
    ]);

    $this->actingAs($this->tecnico)->post('/panel/tickets/2/triaje', ['urgencia' => 'alta'])->assertSessionHasNoErrors();

    $t->refresh();
    expect($t->prioridad)->toBe(Prioridad::Urgente)
        ->and($t->urgencia)->toBe('alta')
        ->and($t->sla_vence_en->equalTo(now()->addMinutes(240)))->toBeTrue()
        ->and($t->primera_respuesta_vence_en->equalTo(now()->addMinutes(60)))->toBeTrue()
        ->and($t->eventos()->where('tipo', 'prioridad')->count())->toBe(1);
});

it('una urgencia no válida da error', function () {
    Ticket::factory()->create(['empresa_id' => $this->empresa->id, 'numero' => 2, 'impacto' => 'todos']);

    $this->actingAs($this->tecnico)->post('/panel/tickets/2/triaje', ['urgencia' => 'altisima'])->assertSessionHasErrors('urgencia');
});

it('la ficha del equipo muestra solo sus incidencias', function () {
    Ticket::factory()->count(2)->create(['empresa_id' => $this->empresa->id, 'equipo_id' => $this->proyector->id]);
    Ticket::factory()->create(['empresa_id' => $this->empresa->id]);

    $this->actingAs($this->tecnico)->get("/panel/equipos/{$this->proyector->id}")->assertInertia(fn (Assert $p) => $p
        ->component('panel/soporte-it/equipo')
        ->where('equipo.codigo', 'PRY-012')
        ->has('tickets', 2));
});

it('el equipo de otra empresa da 404', function () {
    $ajeno = Equipo::withoutGlobalScopes()->create(['empresa_id' => Empresa::factory()->create()->id, 'codigo' => 'X', 'tipo' => 'otro', 'ubicacion' => 'Y']);

    $this->actingAs($this->tecnico)->get("/panel/equipos/{$ajeno->id}")->assertNotFound();
});

it('el administrador da de alta equipos con código único', function () {
    $this->actingAs($this->admin)->post('/panel/equipos', ['codigo' => 'pc-001', 'tipo' => 'sobremesa', 'ubicacion' => 'Secretaría'])->assertSessionHasNoErrors();
    $this->actingAs($this->admin)->post('/panel/equipos', ['codigo' => 'PC-001', 'tipo' => 'sobremesa', 'ubicacion' => 'Otra'])->assertSessionHasErrors('codigo');

    expect(Equipo::where('codigo', 'PC-001')->count())->toBe(1);
});

it('la bandeja busca por código de equipo', function () {
    Ticket::factory()->create(['empresa_id' => $this->empresa->id, 'numero' => 5, 'equipo_id' => $this->proyector->id]);
    Ticket::factory()->create(['empresa_id' => $this->empresa->id, 'numero' => 6]);

    $this->actingAs($this->tecnico)->get('/panel?q=pry-012')->assertInertia(fn (Assert $p) => $p
        ->has('tickets.data', 1)
        ->where('tickets.data.0.numero', 5)
        ->where('tickets.data.0.extra', 'PRY-012 · Aula 12'));
});

it('el detalle del panel incluye equipo, impacto y urgencia', function () {
    Ticket::factory()->create(['empresa_id' => $this->empresa->id, 'numero' => 7, 'equipo_id' => $this->proyector->id, 'impacto' => 'individual', 'urgencia' => 'media']);

    $this->actingAs($this->tecnico)->get('/panel/tickets/7')->assertInertia(fn (Assert $p) => $p
        ->where('ticket.equipo.codigo', 'PRY-012')
        ->where('ticket.impacto', 'individual')
        ->where('ticket.urgencia', 'media'));
});
