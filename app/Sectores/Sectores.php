<?php

namespace App\Sectores;

use App\Enums\SectorClave;

final class Sectores
{
    public static function para(SectorClave $clave): Sector
    {
        return match ($clave) {
            SectorClave::Taller => new Taller,
            SectorClave::Fincas => new Fincas,
            SectorClave::SoporteIt => new SoporteIt,
        };
    }
}
