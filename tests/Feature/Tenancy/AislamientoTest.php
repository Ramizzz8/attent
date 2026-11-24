<?php

use App\Enums\Rol;
use App\Enums\SectorClave;
use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\User;
use App\Tenancy\EmpresaActual;

it('un empleado no ve clientes de otra empresa', function () {
    [$a, $b] = Empresa::factory()->count(2)->create();
    Cliente::factory()->for($b)->create(['email' => 'x@b.es']);
    app(EmpresaActual::class)->set($a);

    expect(Cliente::where('email', 'x@b.es')->exists())->toBeFalse();
});

it('rellena empresa_id al crear con empresa fijada', function () {
    $a = Empresa::factory()->create();
    app(EmpresaActual::class)->set($a);

    expect(Cliente::create(['nombre' => 'Ana', 'email' => 'a@a.es'])->empresa_id)->toBe($a->id);
});

it('el mismo email puede ser cliente de dos empresas', function () {
    [$a, $b] = Empresa::factory()->count(2)->create();
    Cliente::factory()->for($a)->create(['email' => 'mismo@x.es']);
    Cliente::factory()->for($b)->create(['email' => 'mismo@x.es']);

    expect(Cliente::withoutGlobalScopes()->where('email', 'mismo@x.es')->count())->toBe(2);
});

it('el email del cliente es único dentro de una empresa', function () {
    $a = Empresa::factory()->create();
    Cliente::factory()->for($a)->create(['email' => 'rep@x.es']);
    Cliente::factory()->for($a)->create(['email' => 'rep@x.es']);
})->throws(Illuminate\Database\UniqueConstraintViolationException::class);

it('el middleware del panel fija la empresa del empleado', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->get('/panel')->assertOk();

    expect(app(EmpresaActual::class)->id())->toBe($user->empresa_id);
});

it('empresa y usuario exponen sector y rol tipados', function () {
    $e = Empresa::factory()->create(['sector' => SectorClave::Fincas]);
    $u = User::factory()->for($e)->admin()->create();

    expect($e->fresh()->sector)->toBe(SectorClave::Fincas)
        ->and($e->color_primario)->toBe('#2563eb')
        ->and($u->rol)->toBe(Rol::Admin)
        ->and($u->esAdmin())->toBeTrue()
        ->and(User::factory()->create()->esAdmin())->toBeFalse();
});
