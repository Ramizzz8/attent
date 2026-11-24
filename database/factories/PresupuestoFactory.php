<?php

namespace Database\Factories;

use App\Models\Presupuesto;
use App\Models\Ticket;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Presupuesto> */
class PresupuestoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'ticket_id' => Ticket::factory()->taller(),
            'version' => fn (array $a) => Presupuesto::where('ticket_id', $a['ticket_id'])->max('version') + 1,
            'estado' => 'pendiente',
            'iva_pct' => 21,
        ];
    }
}
