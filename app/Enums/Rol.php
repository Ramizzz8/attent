<?php

namespace App\Enums;

enum Rol: string
{
    case Admin = 'admin';
    case Empleado = 'empleado';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Admin => 'Administrador',
            self::Empleado => 'Empleado',
        };
    }
}
