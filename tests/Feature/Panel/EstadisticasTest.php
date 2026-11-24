<?php

use App\Models\Empresa;
use App\Models\Ticket;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->freezeSecond();
    $this->empresa = Empresa::factory()->soporteIt()->create();
    $this->admin = User::factory()->for($this->empresa)->admin()->create(['name' => 'Ana']);
});

function conObjetivo(Empresa $e, float $horas, ?bool $cumplido, array $attrs = []): Ticket
{
    $t = Ticket::factory()->create(['empresa_id' => $e->id, 'estado' => 'resuelto', 'verificado_en' => now()->subDays(2), 'sla_cumplido' => $cumplido, ...$attrs]);
    $t->eventos()->create(['tipo' => 'estado', 'de' => 'en_curso', 'a' => 'resuelto', 'created_at' => now()->subDays(2)->addMinutes((int) ($horas * 60))]);

    return $t;
}

it('solo el administrador ve las estadísticas', function () {
    $this->actingAs(User::factory()->for($this->empresa)->create())->get('/panel/estadisticas')->assertForbidden();
});

it('calcula el porcentaje de SLA, el tiempo medio y la satisfacción', function () {
    conObjetivo($this->empresa, 8, true, ['valoracion' => 5, 'asignado_a' => $this->admin->id]);
    conObjetivo($this->empresa, 12, true, ['valoracion' => 3]);
    conObjetivo($this->empresa, 30, true);
    conObjetivo($this->empresa, 50, false);
    Ticket::factory()->create(['empresa_id' => $this->empresa->id, 'estado' => 'en_curso', 'asignado_a' => $this->admin->id]);
    Ticket::factory()->create(['empresa_id' => Empresa::factory()->create()->id]); // de otra empresa: no cuenta

    $this->actingAs($this->admin)->get('/panel/estadisticas')->assertInertia(fn (Assert $p) => $p
        ->component('panel/estadisticas')
        ->where('resumen.total', 5)
        ->where('resumen.abiertos', 1)
        ->where('resumen.terminados', 4)
        ->where('resumen.slaCumplido', 75)
        ->where('resumen.horasMedias', 25)
        ->where('resumen.satisfaccion', 4)
        ->where('porEmpleado.0.nombre', 'Ana')
        ->where('porEmpleado.0.total', 2)
        ->has('porCategoria')
        ->has('porSemana'));
});

it('filtra por rango de fechas', function () {
    Ticket::factory()->create(['empresa_id' => $this->empresa->id, 'created_at' => now()->subDays(60)]);
    Ticket::factory()->create(['empresa_id' => $this->empresa->id, 'created_at' => now()->subDays(5)]);

    $this->actingAs($this->admin)->get('/panel/estadisticas')->assertInertia(fn (Assert $p) => $p->where('resumen.total', 1));
    $this->actingAs($this->admin)->get('/panel/estadisticas?desde='.now()->subDays(90)->toDateString())->assertInertia(fn (Assert $p) => $p->where('resumen.total', 2));
});

it('incluye a cero las semanas sin incidencias', function () {
    Ticket::factory()->create(['empresa_id' => $this->empresa->id, 'created_at' => now()->subDays(20)]);
    Ticket::factory()->create(['empresa_id' => $this->empresa->id, 'created_at' => now()->subDay()]);

    $this->actingAs($this->admin)->get('/panel/estadisticas')->assertInertia(function (Assert $p) {
        $semanas = collect($p->toArray()['props']['porSemana']);
        expect($semanas->count())->toBeGreaterThanOrEqual(5)
            ->and($semanas->sum('total'))->toBe(2)
            ->and($semanas->where('total', 0)->count())->toBeGreaterThanOrEqual(2);
    });
});

it('sin datos no divide por cero', function () {
    $this->actingAs($this->admin)->get('/panel/estadisticas')->assertInertia(fn (Assert $p) => $p
        ->where('resumen.total', 0)
        ->where('resumen.slaCumplido', null)
        ->where('resumen.horasMedias', null)
        ->where('resumen.satisfaccion', null));
});
