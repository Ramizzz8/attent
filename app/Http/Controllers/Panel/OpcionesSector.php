<?php

namespace App\Http\Controllers\Panel;

use App\Enums\SectorClave;
use App\Models\Empresa;
use App\Models\Ticket;
use App\Models\Vehiculo;
use Illuminate\Database\Eloquent\Builder;

/** Extensiones de la bandeja que dependen del sector (filtros, búsqueda y dato extra por fila). */
final class OpcionesSector
{
    /** @return array<string, mixed> */
    public static function bandeja(Empresa $empresa): array
    {
        return [];
    }

    /** @return list<string> relaciones a precargar en la bandeja */
    public static function relaciones(Empresa $empresa): array
    {
        return match ($empresa->sector) {
            SectorClave::Taller => ['vehiculo'],
            default => [],
        };
    }

    /** Añade condiciones OR de búsqueda propias del sector (matrícula, código de equipo…). */
    public static function buscar(Builder $q, string $texto, Empresa $empresa): void
    {
        if ($empresa->sector === SectorClave::Taller) {
            $matricula = Vehiculo::normalizarMatricula($texto);
            if ($matricula !== '') {
                $q->orWhereHas('vehiculo', fn (Builder $v) => $v->where('matricula', 'like', "%{$matricula}%"));
            }
        }
    }

    /** Dato corto que acompaña al asunto en la fila de la bandeja. */
    public static function extraFila(Ticket $ticket): ?string
    {
        return match ($ticket->empresa->sector) {
            SectorClave::Taller => $ticket->vehiculo?->matricula,
            default => null,
        };
    }
}
