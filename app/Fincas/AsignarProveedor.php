<?php

namespace App\Fincas;

use App\Models\Proveedor;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\ProveedorAsignado;
use App\Tickets\CambiarEstado;
use App\Tickets\TransicionNoPermitida;

class AsignarProveedor
{
    /** Estados desde los que se puede asignar (o cambiar) el proveedor. */
    private const DESDE = ['reportada', 'en_revision', 'asignada_proveedor', 'visita_programada'];

    public function __construct(private CambiarEstado $cambiarEstado) {}

    public function ejecutar(Ticket $ticket, Proveedor $proveedor, User $actor): void
    {
        if (! in_array($ticket->estado, self::DESDE, true)) {
            throw new TransicionNoPermitida('Esta incidencia ya no admite cambiar de proveedor.');
        }

        if ($ticket->edificio_id === null || ! $proveedor->atiende($ticket->edificio_id)) {
            throw new TransicionNoPermitida('Ese proveedor no atiende este edificio.');
        }

        $ticket->update(['proveedor_id' => $proveedor->id, 'visita_programada_en' => null]);
        $ticket->eventos()->create([
            'tipo' => 'proveedor',
            'user_id' => $actor->id,
            'datos' => ['proveedor' => $proveedor->nombre],
        ]);

        if ($ticket->estado !== 'asignada_proveedor') {
            $this->cambiarEstado->ejecutar($ticket, 'asignada_proveedor', $actor, sistema: true);
        }

        $proveedor->notify(new ProveedorAsignado($ticket->fresh()));
    }
}
