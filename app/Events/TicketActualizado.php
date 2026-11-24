<?php

namespace App\Events;

use App\Models\Ticket;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Aviso en tiempo real al panel de la empresa. Va en cola: si el servidor de
 * WebSockets no está disponible, la petición del usuario no falla.
 */
class TicketActualizado implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    public int $empresaId;

    public int $numero;

    public string $asunto;

    /** @param 'creado'|'estado'|'mensaje'|'asignado'|'prioridad' $tipo */
    public function __construct(Ticket $ticket, public string $tipo)
    {
        $this->empresaId = $ticket->empresa_id;
        $this->numero = $ticket->numero;
        $this->asunto = $ticket->asunto;
    }

    /** @return list<PrivateChannel> */
    public function broadcastOn(): array
    {
        return [new PrivateChannel("empresa.{$this->empresaId}")];
    }

    public function broadcastAs(): string
    {
        return 'ticket.actualizado';
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return ['numero' => $this->numero, 'asunto' => $this->asunto, 'tipo' => $this->tipo];
    }
}
