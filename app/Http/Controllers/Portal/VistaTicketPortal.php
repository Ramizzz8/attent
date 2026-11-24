<?php

namespace App\Http\Controllers\Portal;

use App\Models\Ticket;

/** Lo que el cliente puede ver de su ticket (nunca notas internas ni datos del equipo). */
final class VistaTicketPortal
{
    /** @return array<string, mixed> */
    public static function datos(Ticket $ticket): array
    {
        return [
            'id' => $ticket->id,
            'numero' => $ticket->numero,
            'asunto' => $ticket->asunto,
            'descripcion' => $ticket->descripcion,
            'categoria' => $ticket->sector()->categorias()[$ticket->categoria] ?? $ticket->categoria,
            'estado' => $ticket->estado,
            'estadoEtiqueta' => $ticket->etiquetaEstado(),
            'datos' => $ticket->datos ?? [],
            'creado' => $ticket->created_at->toIso8601String(),
            'historial' => $ticket->eventos()
                ->where('tipo', 'estado')
                ->orderBy('id')
                ->get()
                ->map(fn ($e) => [
                    'estado' => $ticket->sector()->estados()[$e->a] ?? $e->a,
                    'fecha' => $e->created_at->toIso8601String(),
                ]),
        ];
    }
}
