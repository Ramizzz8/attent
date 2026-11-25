<?php

namespace App\Portal;

use App\Models\Ticket;
use App\Sectores\Sector;
use App\Tickets\ReabrirTicket;

/** Lo que ve el cliente sobre el avance de su incidencia y lo que le toca hacer. */
final class ProgresoTicket
{
    /** @return list<array{estado: string, etiqueta: string, situacion: 'hecho'|'actual'|'pendiente'}> */
    public static function progreso(Ticket $ticket): array
    {
        $sector = $ticket->sector();
        $recorrido = $sector->recorrido();
        $etiquetas = $sector->estados();
        $actual = $ticket->estado;
        $item = fn (string $e, string $s) => ['estado' => $e, 'etiqueta' => $etiquetas[$e] ?? $e, 'situacion' => $s];

        // Solo cuenta como hecho lo que de verdad pasó: cada cambio de estado deja un evento.
        $alcanzados = $ticket->eventos()->where('tipo', 'estado')->pluck('a')->push($sector->estadoInicial())->all();
        $hechos = fn (array $pasos) => array_map(
            fn ($e) => $item($e, 'hecho'),
            array_values(array_filter($pasos, fn ($e) => in_array($e, $alcanzados, true))),
        );

        $posicion = array_search($actual, $recorrido, true);
        if ($posicion !== false) {
            return [
                ...$hechos(array_slice($recorrido, 0, $posicion)),
                $item($actual, 'actual'),
                ...array_map(fn ($e) => $item($e, 'pendiente'), array_slice($recorrido, $posicion + 1)),
            ];
        }

        // Estado fuera del recorrido típico (rechazado, esperando piezas, pendiente del usuario, descartada…):
        // va detrás del último estado del recorrido que la incidencia ya alcanzó.
        $ultimo = 0;
        foreach ($recorrido as $i => $e) {
            if (in_array($e, $alcanzados, true)) {
                $ultimo = $i;
            }
        }

        $pasos = [...$hechos(array_slice($recorrido, 0, $ultimo + 1)), $item($actual, 'actual')];

        if (! in_array($actual, $sector->estadosTerminados(), true)) {
            // Solo lo que todavía puede pasar desde aquí (tras rechazar el presupuesto no hay reparación).
            $alcanzables = self::alcanzables($sector, $actual);
            foreach (array_slice($recorrido, $ultimo + 1) as $e) {
                if (in_array($e, $alcanzables, true)) {
                    $pasos[] = $item($e, 'pendiente');
                }
            }
        }

        return $pasos;
    }

    /** @return array{tipo: 'presupuesto'|'responder'|'valorar', texto: string}|null */
    public static function accionPendiente(Ticket $ticket): ?array
    {
        return match (true) {
            $ticket->estado === 'presupuesto_enviado' && $ticket->presupuestoVigente()?->estado === 'pendiente' => [
                'tipo' => 'presupuesto',
                'texto' => 'Revisa el presupuesto de abajo y apruébalo o recházalo. No empezaremos sin tu respuesta.',
            ],
            $ticket->estado === 'pendiente_usuario' => [
                'tipo' => 'responder',
                'texto' => 'El equipo necesita tu respuesta para seguir: escríbela en la conversación.',
            ],
            $ticket->puedeValorarse() => [
                'tipo' => 'valorar',
                'texto' => ReabrirTicket::puede($ticket)
                    ? '¿Está resuelto? Valora la atención o, si el problema sigue, reabre la incidencia.'
                    : '¿Está resuelto? Valora la atención que has recibido.',
            ],
            default => null,
        };
    }

    /** @return list<string> estados a los que se puede llegar desde $desde siguiendo las transiciones del sector */
    private static function alcanzables(Sector $sector, string $desde): array
    {
        $transiciones = $sector->transiciones();
        $vistos = [];
        $pendientes = [$desde];
        while ($pendientes !== []) {
            foreach ($transiciones[array_pop($pendientes)] ?? [] as $siguiente) {
                if (! in_array($siguiente, $vistos, true)) {
                    $vistos[] = $siguiente;
                    $pendientes[] = $siguiente;
                }
            }
        }

        return $vistos;
    }
}
