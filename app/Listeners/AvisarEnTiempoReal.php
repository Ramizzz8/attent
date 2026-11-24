<?php

namespace App\Listeners;

use App\Events\MensajeCreado;
use App\Events\TicketActualizado;
use App\Events\TicketEstadoCambiado;
use App\Models\Ticket;

/** Traduce los eventos de dominio a avisos en tiempo real para el panel. */
class AvisarEnTiempoReal
{
    public function estadoCambiado(TicketEstadoCambiado $evento): void
    {
        $this->emitir($evento->ticket, 'estado');
    }

    public function mensajeCreado(MensajeCreado $evento): void
    {
        $this->emitir($evento->mensaje->ticket, 'mensaje');
    }

    public static function emitir(Ticket $ticket, string $tipo): void
    {
        if ($ticket->verificado_en !== null) {
            TicketActualizado::dispatch($ticket, $tipo);
        }
    }
}
