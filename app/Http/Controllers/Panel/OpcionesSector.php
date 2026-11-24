<?php

namespace App\Http\Controllers\Panel;

use App\Models\Empresa;
use Illuminate\Database\Eloquent\Builder;

/** Extensiones del panel que dependen del sector (filtros y búsqueda propios). */
final class OpcionesSector
{
    /** @return array<string, mixed> */
    public static function bandeja(Empresa $empresa): array
    {
        return [];
    }

    /** Añade condiciones OR de búsqueda propias del sector (matrícula, código de equipo…). */
    public static function buscar(Builder $q, string $like): void {}
}
