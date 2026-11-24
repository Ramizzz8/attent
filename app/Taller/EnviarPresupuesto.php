<?php

namespace App\Taller;

use App\Models\Presupuesto;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\PresupuestoEnviado;
use App\Tickets\CambiarEstado;
use App\Tickets\TransicionNoPermitida;
use Illuminate\Support\Facades\DB;

class EnviarPresupuesto
{
    /** Estados desde los que se puede enviar (o reenviar) un presupuesto. */
    private const DESDE = ['en_diagnostico', 'rechazado'];

    public function __construct(private CambiarEstado $cambiarEstado) {}

    /** @param list<array{tipo: string, concepto: string, cantidad: float|int, precio_unitario: float|int}> $lineas */
    public function ejecutar(Ticket $ticket, array $lineas, float $ivaPct, User $actor): Presupuesto
    {
        if (! in_array($ticket->fresh()->estado, self::DESDE, true)) {
            throw new TransicionNoPermitida('Solo se puede enviar un presupuesto tras el diagnóstico o si el anterior fue rechazado.');
        }

        $presupuesto = DB::transaction(function () use ($ticket, $lineas, $ivaPct) {
            $p = $ticket->presupuestos()->create([
                'version' => (int) $ticket->presupuestos()->max('version') + 1,
                'estado' => 'pendiente',
                'iva_pct' => $ivaPct,
            ]);
            $p->lineas()->createMany(array_map(fn ($l) => [
                'tipo' => $l['tipo'],
                'concepto' => $l['concepto'],
                'cantidad' => $l['cantidad'],
                'precio_unitario' => $l['precio_unitario'],
            ], $lineas));

            return $p;
        });

        $this->cambiarEstado->ejecutar($ticket, 'presupuesto_enviado', $actor, sistema: true);
        $ticket->cliente->notify(new PresupuestoEnviado($presupuesto));

        return $presupuesto->load('lineas');
    }
}
