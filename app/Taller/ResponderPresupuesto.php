<?php

namespace App\Taller;

use App\Models\Cliente;
use App\Models\Presupuesto;
use App\Tickets\CambiarEstado;
use Illuminate\Validation\ValidationException;

class ResponderPresupuesto
{
    public function __construct(private CambiarEstado $cambiarEstado) {}

    public function ejecutar(Presupuesto $presupuesto, Cliente $cliente, bool $aprueba, ?string $comentario = null): void
    {
        $ticket = $presupuesto->ticket;
        abort_unless($ticket->cliente_id === $cliente->id, 404);

        $vigente = $ticket->presupuestoVigente();
        if (! $vigente?->is($presupuesto) || $presupuesto->estado !== 'pendiente' || $ticket->estado !== 'presupuesto_enviado') {
            throw ValidationException::withMessages(['presupuesto' => 'Este presupuesto ya no se puede responder.']);
        }

        $presupuesto->update([
            'estado' => $aprueba ? 'aprobado' : 'rechazado',
            'respondido_en' => now(),
            'comentario_cliente' => $comentario,
        ]);

        $this->cambiarEstado->ejecutar($ticket, $aprueba ? 'aprobado' : 'rechazado', $cliente, sistema: true);
    }
}
