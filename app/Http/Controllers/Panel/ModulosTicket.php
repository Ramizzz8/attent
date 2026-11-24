<?php

namespace App\Http\Controllers\Panel;

use App\Enums\SectorClave;
use App\Models\Presupuesto;
use App\Models\Proveedor;
use App\Models\Ticket;

/** Datos de los módulos propios del sector que se muestran en el detalle del ticket del panel. */
final class ModulosTicket
{
    /** @return array<string, mixed> */
    public static function datos(Ticket $ticket): array
    {
        return match ($ticket->empresa->sector) {
            SectorClave::Taller => self::taller($ticket),
            SectorClave::Fincas => self::fincas($ticket),
            default => [],
        };
    }

    /** @return array<string, mixed> */
    private static function fincas(Ticket $ticket): array
    {
        return [
            'edificio' => $ticket->edificio?->nombre,
            'zona' => $ticket->zona?->nombre,
            'afectados' => $ticket->afectados()->count(),
            'afectadosNombres' => $ticket->afectados()->pluck('nombre'),
            'proveedor' => $ticket->proveedor?->only(['id', 'nombre', 'oficio', 'telefono', 'email']),
            'visita' => $ticket->visita_programada_en?->toIso8601String(),
            'proveedoresDisponibles' => $ticket->edificio_id
                ? Proveedor::whereHas('edificios', fn ($q) => $q->where('edificios.id', $ticket->edificio_id))
                    ->orderBy('nombre')->get(['id', 'nombre', 'oficio'])
                : [],
            'puedeAsignarProveedor' => in_array($ticket->estado, ['reportada', 'en_revision', 'asignada_proveedor', 'visita_programada'], true),
        ];
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
