<?php

namespace App\Http\Controllers\Panel;

use App\Enums\SectorClave;
use App\Models\Presupuesto;
use App\Models\Ticket;

/** Datos de los módulos propios del sector que se muestran en el detalle del ticket del panel. */
final class ModulosTicket
{
    /** @return array<string, mixed> */
    public static function datos(Ticket $ticket): array
    {
        return match ($ticket->empresa->sector) {
            SectorClave::Taller => self::taller($ticket),
            default => [],
        };
    }

    /** @return array<string, mixed> */
    private static function taller(Ticket $ticket): array
    {
        $vehiculo = $ticket->vehiculo;

        return [
            'vehiculo' => $vehiculo ? [
                'matricula' => $vehiculo->matricula,
                'marca' => $vehiculo->marca,
                'modelo' => $vehiculo->modelo,
                'km' => $vehiculo->km,
                'historial' => $vehiculo->tickets()
                    ->where('id', '!=', $ticket->id)
                    ->latest()
                    ->get()
                    ->map(fn (Ticket $t) => [
                        'numero' => $t->numero,
                        'asunto' => $t->asunto,
                        'estado' => $t->etiquetaEstado(),
                        'fecha' => $t->created_at->toIso8601String(),
                    ]),
            ] : null,
            'presupuestos' => $ticket->presupuestos()->with('lineas')->orderByDesc('version')->get()
                ->map(fn (Presupuesto $p) => $p->resumen()),
            'puedePresupuestar' => in_array($ticket->estado, ['en_diagnostico', 'rechazado'], true),
        ];
    }
}
