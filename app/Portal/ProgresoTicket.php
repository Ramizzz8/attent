<?php

namespace App\Portal;

use App\Models\Ticket;

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

        $posicion = array_search($actual, $recorrido, true);
        if ($posicion !== false) {
            return array_map(
                fn ($e, $i) => $item($e, $i < $posicion ? 'hecho' : ($i === $posicion ? 'actual' : 'pendiente')),
                $recorrido,
                array_keys($recorrido),
            );
        }

        // Estado fuera del recorrido típico (rechazado, esperando piezas, pendiente del usuario, descartada…):
        // va detrás del último estado del recorrido que la incidencia ya alcanzó.
        $alcanzados = $ticket->eventos()->where('tipo', 'estado')->pluck('a')->push($sector->estadoInicial())->all();
        $ultimo = 0;
        foreach ($recorrido as $i => $e) {
            if (in_array($e, $alcanzados, true)) {
                $ultimo = $i;
            }
        }

        $pasos = array_map(fn ($e) => $item($e, 'hecho'), array_slice($recorrido, 0, $ultimo + 1));
        $pasos[] = $item($actual, 'actual');

        if (! in_array($actual, $sector->estadosTerminados(), true)) {
            foreach (array_slice($recorrido, $ultimo + 1) as $e) {
                $pasos[] = $item($e, 'pendiente');
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
                'texto' => '¿Está resuelto? Valora la atención o, si el problema sigue, reabre la incidencia.',
            ],
            default => null,
        };
    }
}
