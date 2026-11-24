<?php

namespace Database\Factories;

use App\Enums\Prioridad;
use App\Enums\SectorClave;
use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\Ticket;
use App\Sectores\Sectores;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Ticket> */
class TicketFactory extends Factory
{
    private static int $numero = 0;

    public function definition(): array
    {
        return [
            'empresa_id' => Empresa::factory(),
            'numero' => ++self::$numero,
            'cliente_id' => fn (array $a) => Cliente::factory()->create(['empresa_id' => $a['empresa_id']])->id,
            'estado' => fn (array $a) => Empresa::find($a['empresa_id'])->motor()->estadoInicial(),
            'prioridad' => Prioridad::Media,
            'categoria' => fn (array $a) => array_key_first(Empresa::find($a['empresa_id'])->motor()->categorias()),
            'asunto' => fake()->sentence(4),
            'descripcion' => fake()->paragraph(),
            'verificado_en' => now(),
            'sla_vence_en' => now()->addDay(),
        ];
    }

    public function sector(SectorClave $clave): static
    {
        return $this->state(fn () => ['empresa_id' => Empresa::factory()->state(['sector' => $clave])]);
    }

    public function taller(): static
    {
        return $this->sector(SectorClave::Taller);
    }

    public function fincas(): static
    {
        return $this->sector(SectorClave::Fincas);
    }

    public function soporteIt(): static
    {
        return $this->sector(SectorClave::SoporteIt);
    }

    public function noVerificado(): static
    {
        return $this->state(['verificado_en' => null]);
    }
}
