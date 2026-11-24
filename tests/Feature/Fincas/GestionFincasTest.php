<?php

require_once __DIR__.'/FincasHelpers.php';

use App\Models\Edificio;
use App\Models\Proveedor;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    montarFincas($this);
    $this->admin = User::factory()->for($this->empresa)->admin()->create();
});

it('el administrador ve sus edificios con zonas y proveedores', function () {
    $this->actingAs($this->admin)->get('/panel/edificios')->assertInertia(fn (Assert $p) => $p
        ->component('panel/fincas/edificios')
        ->has('edificios', 2)
        ->has('edificios.0.zonas', 2)
        ->has('proveedores', 1));
});

it('crea un edificio con slug único y sus zonas', function () {
    $this->actingAs($this->admin)->post('/panel/edificios', [
        'nombre' => 'Residencial Mar', 'direccion' => 'Otra calle 3', 'zonas' => "Ascensor*\nGaraje\nPiscina",
    ])->assertSessionHasNoErrors();

    $e = Edificio::latest('id')->first();
    expect($e->slug)->toBe('residencial-mar-2')
        ->and($e->zonas()->pluck('nombre')->all())->toBe(['Ascensor', 'Garaje', 'Piscina'])
        ->and($e->zonas()->where('prioridad_alta', true)->pluck('nombre')->all())->toBe(['Ascensor']);
});

it('crea un proveedor con los edificios que atiende', function () {
    $this->actingAs($this->admin)->post('/panel/proveedores', [
        'nombre' => 'Ascensores Sur', 'oficio' => 'Ascensorista', 'email' => 'sur@x.test',
        'edificios' => [$this->edificio->id, $this->otroEdificio->id],
    ])->assertSessionHasNoErrors();

    expect(Proveedor::where('nombre', 'Ascensores Sur')->sole()->edificios)->toHaveCount(2);
});

it('no se puede asociar un edificio de otra empresa', function () {
    $ajeno = Edificio::withoutGlobalScopes()->create(['empresa_id' => App\Models\Empresa::factory()->fincas()->create()->id, 'nombre' => 'X', 'direccion' => 'Y', 'slug' => 'x']);

    $this->actingAs($this->admin)->post('/panel/proveedores', [
        'nombre' => 'P', 'oficio' => 'O', 'email' => 'p@x.test', 'edificios' => [$ajeno->id],
    ])->assertSessionHasErrors('edificios.0');
});

it('un empleado no gestiona edificios', function () {
    $empleado = User::factory()->for($this->empresa)->create();

    $this->actingAs($empleado)->get('/panel/edificios')->assertForbidden();
});
