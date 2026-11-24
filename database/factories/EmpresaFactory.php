<?php

namespace Database\Factories;

use App\Enums\SectorClave;
use App\Models\Empresa;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Empresa> */
class EmpresaFactory extends Factory
{
    public function definition(): array
    {
        $nombre = fake()->unique()->company();

        return [
            'nombre' => $nombre,
            'slug' => Str::slug($nombre).'-'.Str::lower(Str::random(4)),
            'sector' => SectorClave::SoporteIt,
        ];
    }

    public function taller(): static
    {
        return $this->state(['sector' => SectorClave::Taller]);
    }

    public function fincas(): static
    {
        return $this->state(['sector' => SectorClave::Fincas]);
    }

    public function soporteIt(): static
    {
        return $this->state(['sector' => SectorClave::SoporteIt]);
    }
}
