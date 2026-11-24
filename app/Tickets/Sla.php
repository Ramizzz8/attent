<?php

namespace App\Tickets;

use App\Models\Ticket;

/**
 * Plazos de resolución (SLA) en horas naturales.
 * El reloj se para en los estados de pausa del sector; el tiempo pausado
 * se acumula en sla_pausa_segundos y desplaza el vencimiento.
 */
class Sla
{
    /** Fija los vencimientos desde el momento en que el ticket queda verificado. */
    public function iniciar(Ticket $ticket): void
    {
        $inicio = $ticket->verificado_en ?? now();
        $sector = $ticket->sector();

        $ticket->sla_vence_en = $inicio->copy()->addMinutes($sector->slaMinutos($ticket->prioridad, $ticket->empresa));

        $primera = $sector->primeraRespuestaMinutos($ticket->prioridad);
        $ticket->primera_respuesta_vence_en = $primera === null ? null : $inicio->copy()->addMinutes($primera);
    }

    public function pausar(Ticket $ticket): void
    {
        $ticket->sla_pausado_en ??= now();
    }

    public function reanudar(Ticket $ticket): void
    {
        if ($ticket->sla_pausado_en === null) {
            return;
        }

        $segundos = (int) $ticket->sla_pausado_en->diffInSeconds(now(), true);
        $ticket->sla_pausa_segundos += $segundos;
        $ticket->sla_vence_en = $ticket->sla_vence_en?->copy()->addSeconds($segundos);
        $ticket->sla_pausado_en = null;
    }

    /** Recalcula el vencimiento de resolución tras un cambio de prioridad (la primera respuesta no cambia). */
    public function recalcular(Ticket $ticket): void
    {
        $inicio = $ticket->verificado_en ?? $ticket->created_at ?? now();
        $minutos = $ticket->sector()->slaMinutos($ticket->prioridad, $ticket->empresa);

        $ticket->sla_vence_en = $inicio->copy()->addMinutes($minutos)->addSeconds($ticket->sla_pausa_segundos);
    }

    /** 'ok' | 'en_riesgo' | 'vencido' | 'pausado' | 'cumplido' | 'incumplido' | null (sin SLA) */
    public function estado(Ticket $ticket): ?string
    {
        if ($ticket->sla_cumplido !== null) {
            return $ticket->sla_cumplido ? 'cumplido' : 'incumplido';
        }

        if ($ticket->sla_vence_en === null || in_array($ticket->estado, $ticket->sector()->estadosTerminados(), true)) {
            return null;
        }

        if ($ticket->sla_pausado_en !== null) {
            return 'pausado';
        }

        $restante = now()->diffInSeconds($ticket->sla_vence_en, false);
        if ($restante < 0) {
            return 'vencido';
        }

        $total = $ticket->sector()->slaMinutos($ticket->prioridad, $ticket->empresa) * 60;

        return $restante < $total * 0.2 ? 'en_riesgo' : 'ok';
    }
}
