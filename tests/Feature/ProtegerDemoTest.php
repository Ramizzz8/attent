<?php

use App\Models\Empresa;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->empresa = Empresa::factory()->create(['slug' => 'x']);
    $this->demo = User::factory()->for($this->empresa)->admin()->create(['email' => 'admin@x.test']);
    $this->empleadoDemo = User::factory()->for($this->empresa)->create(['email' => 'empleado1@x.test']);
});

const AVISO_DEMO = 'Desactivado en la demo: esta cuenta la comparten todos los visitantes.';

it('en la demo una cuenta demo no puede cambiar su contraseña', function () {
    config(['attent.demo' => true]);

    $this->actingAs($this->demo)
        ->put('/settings/password', ['current_password' => 'password', 'password' => 'nueva-clave-123', 'password_confirmation' => 'nueva-clave-123'])
        ->assertSessionHasErrors(['demo' => AVISO_DEMO]);

    expect(Hash::check('password', $this->demo->fresh()->password))->toBeTrue();
});

it('en la demo una cuenta demo no puede cambiar su email ni borrarse', function () {
    config(['attent.demo' => true]);

    $this->actingAs($this->demo)->patch('/settings/profile', ['name' => 'X', 'email' => 'otro@x.es'])->assertSessionHasErrors('demo');
    $this->actingAs($this->demo)->delete('/settings/profile', ['password' => 'password'])->assertSessionHasErrors('demo');

    expect($this->demo->fresh()->email)->toBe('admin@x.test');
});

it('en la demo una cuenta demo no puede activar la verificación en dos pasos', function () {
    config(['attent.demo' => true]);

    $this->actingAs($this->demo)->post('/user/two-factor-authentication')->assertSessionHasErrors('demo');

    expect($this->demo->fresh()->two_factor_secret)->toBeNull();
});

it('en la demo no se puede dar de baja ni cambiar el rol de una cuenta demo', function () {
    config(['attent.demo' => true]);

    $this->actingAs($this->demo)->delete("/panel/empleados/{$this->empleadoDemo->id}")->assertSessionHasErrors('demo');
    $this->actingAs($this->demo)->patch("/panel/empleados/{$this->empleadoDemo->id}", ['rol' => 'admin'])->assertSessionHasErrors('demo');

    expect(User::find($this->empleadoDemo->id)->rol->value)->toBe('empleado');
});

it('fuera de la demo las cuentas cambian su contraseña con normalidad', function () {
    config(['attent.demo' => false]);

    $this->actingAs($this->demo)
        ->put('/settings/password', ['current_password' => 'password', 'password' => 'nueva-clave-123', 'password_confirmation' => 'nueva-clave-123'])
        ->assertSessionHasNoErrors();

    expect(Hash::check('nueva-clave-123', $this->demo->fresh()->password))->toBeTrue();
});

it('en la demo una cuenta que no es de demo no se bloquea', function () {
    config(['attent.demo' => true]);
    $real = User::factory()->for($this->empresa)->create(['email' => 'persona@cole.es']);

    $this->actingAs($real)->patch('/settings/profile', ['name' => 'Persona', 'email' => 'persona@cole.es'])->assertSessionHasNoErrors();
});
