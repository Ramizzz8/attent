<?php

namespace App\Http\Controllers\Portal;

use App\Models\Adjunto;
use App\Models\Cliente;
use App\Models\Mensaje;
use App\Models\Ticket;

/** Lo que el cliente puede ver de su ticket (nunca notas internas ni datos del equipo). */
final class VistaTicketPortal
{
    /** @return array<string, mixed> */
    public static function datos(Ticket $ticket): array
    {
        $sector = $ticket->sector();

        return [
            'id' => $ticket->id,
            'numero' => $ticket->numero,
            'asunto' => $ticket->asunto,
            'descripcion' => $ticket->descripcion,
            'categoria' => $sector->categorias()[$ticket->categoria] ?? $ticket->categoria,
            'estado' => $ticket->estado,
            'estadoEtiqueta' => $ticket->etiquetaEstado(),
            'datos' => $ticket->datos ?? [],
            'creado' => $ticket->created_at->toIso8601String(),
            'adjuntos' => $ticket->adjuntos()->whereNull('mensaje_id')->get()->map(fn (Adjunto $a) => $a->resumen()),
            'mensajes' => $ticket->mensajes()
                ->where('interno', false)
                ->with(['adjuntos', 'autor'])
                ->orderBy('id')
                ->get()
                ->map(fn (Mensaje $m) => [
                    'id' => $m->id,
                    'cuerpo' => $m->cuerpo,
                    'delEquipo' => ! $m->autor instanceof Cliente,
                    'autor' => $m->autor instanceof Cliente ? $m->autor->nombre : ($m->esDelEquipo() ? $ticket->empresa->nombre : $m->autor?->nombre),
                    'fecha' => $m->created_at->toIso8601String(),
                    'adjuntos' => $m->adjuntos->map(fn (Adjunto $a) => $a->resumen()),
                ]),
            'historial' => $ticket->eventos()
                ->where('tipo', 'estado')
                ->orderBy('id')
                ->get()
                ->map(fn ($e) => [
                    'estado' => $sector->estados()[$e->a] ?? $e->a,
                    'fecha' => $e->created_at->toIso8601String(),
                ]),
        ];
    }
}
