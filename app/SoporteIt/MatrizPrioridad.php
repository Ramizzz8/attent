<?php

namespace App\SoporteIt;

use App\Enums\Prioridad;

/** Matriz ITIL simplificada: urgencia × impacto → prioridad. */
final class MatrizPrioridad
{
    public const IMPACTOS = ['individual', 'departamento', 'todos'];

    public const URGENCIAS = ['baja', 'media', 'alta'];

    private const MATRIZ = [
        'baja' => ['individual' => Prioridad::Baja, 'departamento' => Prioridad::Baja, 'todos' => Prioridad::Media],
        'media' => ['individual' => Prioridad::Baja, 'departamento' => Prioridad::Media, 'todos' => Prioridad::Alta],
        'alta' => ['individual' => Prioridad::Media, 'departamento' => Prioridad::Alta, 'todos' => Prioridad::Urgente],
    ];

    public static function calcular(string $impacto, string $urgencia): Prioridad
    {
        return self::MATRIZ[$urgencia][$impacto]
            ?? throw new \InvalidArgumentException("Impacto o urgencia no válidos: {$impacto} / {$urgencia}");
    }
}
