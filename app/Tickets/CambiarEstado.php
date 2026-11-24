<?php

namespace App\Tickets;

use App\Events\TicketEstadoCambiado;
use App\Models\Cliente;
use App\Models\Ticket;
use App\Models\TicketEvento;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Único punto por el que cambia el estado de un ticket.
 * Valida la transición contra el sector, gestiona el SLA y deja rastro en el historial.
 */
class CambiarEstado
{
    public function __construct(private Sla $sla) {}

    public function ejecutar(Ticket $ticket, string $destino, User|Cliente|null $actor, ?string $motivo = null, bool $sistema = false): Ticket
    {
        $origenAntes = null;
        $actualizado = DB::transaction(function () use ($ticket, $destino, $actor, $motivo, $sistema, &$origenAntes) {
            // Se recarga bloqueado: si otro empleado lo cambió entretanto, se valida contra el estado real.
            $t = Ticket::withoutGlobalScopes()->whereKey($ticket->id)->lockForUpdate()->firstOrFail();
            $sector = $t->sector();
            $origen = $origenAntes = $t->estado;
            $motivo = filled($motivo) ? trim($motivo) : null;

            if (! array_key_exists($destino, $sector->estados())) {
                throw new TransicionNoPermitida("El estado «{$destino}» no existe.");
            }

            $esRetroceso = ! $sistema && $this->esRetroceso($t, $destino);
            $esAvance = in_array($destino, $sector->transiciones()[$origen] ?? [], true)
                && ($sistema || ! in_array($destino, $sector->estadosSoloSistema(), true));

            if (! $sistema && ! $esAvance && ! $esRetroceso) {
                throw new TransicionNoPermitida(sprintf(
                    'No se puede pasar de «%s» a «%s».',
                    $sector->estados()[$origen] ?? $origen,
                    $sector->estados()[$destino],
                ));
            }

            if (! $esAvance && $esRetroceso && $motivo === null) {
                throw new TransicionNoPermitida('Indica el motivo para volver al estado anterior.');
            }

            if ($error = $sector->validarTransicion($t, $destino, $motivo)) {
                throw new TransicionNoPermitida($error);
            }

            $this->aplicarSla($t, $origen, $destino);
            $this->aplicarFechas($t, $destino);
            $t->estado = $destino;
            $t->save();

            $t->eventos()->create([
                'tipo' => 'estado',
                'de' => $origen,
                'a' => $destino,
                'motivo' => $motivo,
                'user_id' => $actor instanceof User ? $actor->id : null,
                'cliente_id' => $actor instanceof Cliente ? $actor->id : null,
            ]);

            return $t;
        });

        TicketEstadoCambiado::dispatch($actualizado, $sistema, $actor, $origenAntes);

        return $actualizado;
    }

    /**
     * Destinos que se ofrecen al empleado en el panel.
     *
     * @return list<array{estado: string, etiqueta: string, retroceso: bool, principal: bool}>
     */
    public function destinosPosibles(Ticket $ticket): array
    {
        $sector = $ticket->sector();
        $destinos = [];

        foreach ($sector->transiciones()[$ticket->estado] ?? [] as $estado) {
            if (! in_array($estado, $sector->estadosSoloSistema(), true)) {
                $destinos[] = [
                    'estado' => $estado,
                    'etiqueta' => $sector->estados()[$estado],
                    'retroceso' => false,
                    'principal' => ($sector->siguientePaso()[$ticket->estado] ?? null) === $estado,
                ];
            }
        }

        $anterior = $this->ultimoCambio($ticket)?->de;
        if ($anterior !== null && $this->esRetroceso($ticket, $anterior)) {
            $destinos[] = ['estado' => $anterior, 'etiqueta' => $sector->estados()[$anterior], 'retroceso' => true, 'principal' => false];
        }

        return $destinos;
    }

    private function esRetroceso(Ticket $ticket, string $destino): bool
    {
        $ultimo = $this->ultimoCambio($ticket);

        return $ultimo !== null && $ultimo->a === $ticket->estado && $ultimo->de === $destino;
    }

    private function ultimoCambio(Ticket $ticket): ?TicketEvento
    {
        return $ticket->eventos()->where('tipo', 'estado')->latest('id')->first();
    }

    private function aplicarSla(Ticket $t, string $origen, string $destino): void
    {
        $sector = $t->sector();

        if ($destino === $sector->estadoObjetivoSla() && $t->sla_cumplido === null && $t->sla_vence_en !== null) {
            $this->sla->reanudar($t);
            $t->sla_cumplido = now()->lessThanOrEqualTo($t->sla_vence_en);
        }

        $pausaOrigen = in_array($origen, $sector->estadosPausaSla(), true);
        $pausaDestino = in_array($destino, $sector->estadosPausaSla(), true);

        if (! $pausaOrigen && $pausaDestino) {
            $this->sla->pausar($t);
        } elseif ($pausaOrigen && ! $pausaDestino) {
            $this->sla->reanudar($t);
        }
    }

    private function aplicarFechas(Ticket $t, string $destino): void
    {
        $sector = $t->sector();

        if ($destino === $sector->estadoResuelto()) {
            $t->resuelto_en = now();
        } elseif (in_array($destino, $sector->estadosTerminados(), true)) {
            $t->cerrado_en = now();
        } else {
            $t->resuelto_en = null;
            $t->cerrado_en = null;
        }
    }
}
