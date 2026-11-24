<?php

namespace App\Tickets;

use App\Models\Cliente;
use App\Models\Ticket;
use Illuminate\Validation\ValidationException;

/** El cliente reabre una incidencia resuelta (como mucho 7 días después). */
class ReabrirTicket
{
    public const DIAS = 7;

    public function __construct(private CambiarEstado $cambiarEstado, private Sla $sla) {}

    public static function puede(Ticket $ticket): bool
    {
        return $ticket->estado === $ticket->sector()->estadoResuelto()
            && $ticket->resuelto_en !== null
            && $ticket->resuelto_en->greaterThanOrEqualTo(now()->subDays(self::DIAS));
    }

    public function ejecutar(Ticket $ticket, Cliente $cliente, string $motivo): Ticket
    {
        abort_unless($ticket->cliente_id === $cliente->id, 404);

        if (! self::puede($ticket)) {
            throw ValidationException::withMessages(['motivo' => 'Esta incidencia ya no se puede reabrir. Abre una nueva si el problema continúa.']);
        }

        $t = $this->cambiarEstado->ejecutar($ticket, $ticket->sector()->estadoReapertura($ticket), $cliente, $motivo, sistema: true);

        // Plazo nuevo desde la reapertura.
        $t->forceFill(['sla_cumplido' => null, 'sla_pausa_segundos' => 0, 'sla_pausado_en' => null, 'verificado_en' => $t->verificado_en ?? now()]);
        $t->sla_vence_en = now()->addMinutes($t->sector()->slaMinutos($t->prioridad, $t->empresa));
        $t->save();

        $t->eventos()->create(['tipo' => 'reabierto', 'cliente_id' => $cliente->id, 'motivo' => $motivo]);

        return $t;
    }
}
