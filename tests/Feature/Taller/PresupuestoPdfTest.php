<?php

use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\Ticket;
use App\Models\User;
use App\Taller\EnviarPresupuesto;

beforeEach(function () {
    $this->empresa = Empresa::factory()->taller()->create(['slug' => 'talleres-ruiz']);
    $this->cliente = Cliente::factory()->for($this->empresa)->create();
    $this->mecanico = User::factory()->for($this->empresa)->create();
    $t = Ticket::factory()->create(['empresa_id' => $this->empresa->id, 'cliente_id' => $this->cliente->id, 'numero' => 8, 'estado' => 'en_diagnostico']);
    app(EnviarPresupuesto::class)->ejecutar($t, [['tipo' => 'pieza', 'concepto' => 'Pastillas', 'cantidad' => 1, 'precio_unitario' => 50]], 21, $this->mecanico);
});

it('el cliente descarga el PDF de su presupuesto', function () {
    $r = $this->actingAs($this->cliente, 'cliente')->get('/talleres-ruiz/tickets/8/presupuesto/1/pdf');

    $r->assertOk();
    expect($r->headers->get('content-type'))->toContain('application/pdf')
        ->and(substr($r->getContent(), 0, 4))->toBe('%PDF');
});

it('otro cliente no lo descarga', function () {
    $otro = Cliente::factory()->for($this->empresa)->create();

    $this->actingAs($otro, 'cliente')->get('/talleres-ruiz/tickets/8/presupuesto/1/pdf')->assertNotFound();
});

it('el empleado lo descarga desde el panel; una versión inexistente da 404', function () {
    $this->actingAs($this->mecanico)->get('/panel/tickets/8/presupuestos/1/pdf')->assertOk();
    $this->actingAs($this->mecanico)->get('/panel/tickets/8/presupuestos/9/pdf')->assertNotFound();
});

it('un empleado de otra empresa no lo descarga', function () {
    $this->actingAs(User::factory()->create())->get('/panel/tickets/8/presupuestos/1/pdf')->assertNotFound();
});
