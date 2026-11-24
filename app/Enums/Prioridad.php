<?php

namespace App\Enums;

enum Prioridad: string
{
    case Baja = 'baja';
    case Media = 'media';
    case Alta = 'alta';
    case Urgente = 'urgente';

    public function etiqueta(): string
    {
        return ucfirst($this->value);
    }

    public function nivel(): int
    {
        return match ($this) {
            self::Baja => 1,
            self::Media => 2,
            self::Alta => 3,
            self::Urgente => 4,
        };
    }

    public static function desdeNivel(int $nivel): self
    {
        return match (max(1, min(4, $nivel))) {
            1 => self::Baja,
            2 => self::Media,
            3 => self::Alta,
            4 => self::Urgente,
        };
    }

    public function subir(): self
    {
        return self::desdeNivel($this->nivel() + 1);
    }
}
