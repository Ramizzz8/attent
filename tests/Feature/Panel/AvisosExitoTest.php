<?php

use App\Models\Empresa;
use App\Models\Ticket;
use App\Models\User;

beforeEach(function () {
    $this->empresa = Empresa::factory()->soporteIt()->create();
    $this->yo = User::factory()->for($this->empresa)->admin()->create();
    $this->ticket = Ticket::factory()->create(['empresa_id' => $this->empresa->id, 'numero' => 4, 'estado' => 'asignado', 'asignado_a' => $this->yo->id, 'impacto' => 'individual']);
});

it('confirma el cambio de estado', function () {
    $this->actingAs($this->yo)->post('/panel/tickets/4/estado', ['destino' => 'en_curso'])
        ->assertSessionHas('exito', 'Estado cambiado a «En curso».');
});

it('una transición fallida no muestra aviso de éxito', function () {
    $this->actingAs($this->yo)->post('/panel/tickets/4/estado', ['destino' => 'cerrado'])
        ->assertSessionHasErrors('destino')
        ->assertSessionMissing('exito');
});

it('confirma mensajes, notas internas, asignación y triaje', function () {
    $this->actingAs($this->yo)->post('/panel/tickets/4/mensajes', ['cuerpo' => 'Hola'])->assertSessionHas('exito', 'Mensaje enviado al cliente.');
    $this->actingAs($this->yo)->post('/panel/tickets/4/mensajes', ['cuerpo' => 'Ojo', 'interno' => true])->assertSessionHas('exito', 'Nota interna guardada.');
    $this->actingAs($this->yo)->post('/panel/tickets/4/asignar', ['user_id' => null])->assertSessionHas('exito', 'Asignación quitada.');
    $this->actingAs($this->yo)->post('/panel/tickets/4/triaje', ['urgencia' => 'alta'])->assertSessionHas('exito', 'Urgencia actualizada: prioridad Media.');
});

it('confirma que se guardan los ajustes', function () {
    $this->actingAs($this->yo)->post('/panel/ajustes', ['nombre' => 'X', 'color_primario' => '#2563eb'])->assertSessionHas('exito', 'Ajustes guardados.');
});
