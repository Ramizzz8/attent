<?php

namespace App\Fincas;

use App\Enums\Prioridad;
use App\Models\Cliente;
use App\Models\Ticket;
use App\Tickets\Sla;
use Illuminate\Support\Facades\DB;

/**
 * "A mí también me afecta": suma un vecino a una incidencia existente.
 * Con 3 afectados (contando a quien la abrió) la prioridad sube un nivel; con 6, urgente.
 */
class SumarAfectado
{
    public const UMBRAL_SUBIR = 3;

    public const UMBRAL_URGENTE = 6;

    public function __construct(private Sla $sla) {}

    public function ejecutar(Ticket $ticket, Cliente $cliente): void
    {
        if ($cliente->id === $ticket->cliente_id || $cliente->empresa_id !== $ticket->empresa_id) {
            return;
        }

        DB::transaction(function () use ($ticket, $cliente) {
            $ticket->afectados()->syncWithoutDetaching([$cliente->id]);
            $this->recalcularPrioridad($ticket->fresh());
        });
    }

    private function recalcularPrioridad(Ticket $ticket): void
    {
        $total = 1 + $ticket->afectados()->count();
        $base = $ticket->sector()->prioridadInicial([
            'categoria' => $ticket->categoria,
            'zona_id' => $ticket->zona_id,
        ], $ticket->empresa);

        $objetivo = match (true) {
            $total >= self::UMBRAL_URGENTE => Prioridad::Urgente,
            $total >= self::UMBRAL_SUBIR => $base->subir(),
            default => $base,
        };

        // Solo sube, nunca baja.
        if ($objetivo->nivel() <= $ticket->prioridad->nivel()) {
            return;
        }

        $anterior = $ticket->prioridad;
        $ticket->prioridad = $objetivo;
        $this->sla->recalcular($ticket);
        $ticket->save();

        $ticket->eventos()->create([
            'tipo' => 'prioridad',
            'datos' => ['de' => $anterior->etiqueta(), 'a' => $objetivo->etiqueta(), 'motivo' => "{$total} vecinos afectados"],
        ]);
    }
}
