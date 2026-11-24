<?php

namespace App\Enums;

enum SectorClave: string
{
    case Taller = 'taller';
    case Fincas = 'fincas';
    case SoporteIt = 'soporte_it';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Taller => 'Taller mecánico',
            self::Fincas => 'Administrador de fincas',
            self::SoporteIt => 'Soporte informático',
        };
    }
}
