<?php

namespace App\Panel;

use App\Enums\SectorClave;
use App\Models\Equipo;
use App\Models\User;

/**
 * Primeros pasos de la demo pública: tres tareas por sector que apuntan a
 * incidencias reales de los datos de ejemplo (DemoSeeder).
 */
final class GuiaDemo
{
    /** @return list<array{texto: string, enlace: string}>|null */
    public static function para(?User $user): ?array
    {
        if (! config('attent.demo') || ! $user?->esCuentaDemo() || ! $user->empresa) {
            return null;
        }

        return match ($user->empresa->sector) {
            SectorClave::Taller => [
                ['texto' => 'Pasa la incidencia #1 a diagnóstico', 'enlace' => '/panel/tickets/1'],
                ['texto' => 'Envía el presupuesto de la incidencia #3', 'enlace' => '/panel/tickets/3'],
                ['texto' => 'Mira un presupuesto como cliente: entra con jorge@ejemplo.test', 'enlace' => '/talleres-ruiz/acceder'],
            ],
            SectorClave::Fincas => [
                ['texto' => 'Abre la #1: seis vecinos afectados la volvieron urgente', 'enlace' => '/panel/tickets/1'],
                ['texto' => 'Revisa la #2 y asígnale un proveedor', 'enlace' => '/panel/tickets/2'],
                ['texto' => 'Avisa de una avería del ascensor como vecino y verás el aviso de duplicado', 'enlace' => '/fincas-levante/edificio/residencial-mar'],
            ],
            SectorClave::SoporteIt => [
                ['texto' => 'Asígnate la #1: la red está caída y es urgente', 'enlace' => '/panel/tickets/1'],
                ['texto' => 'Haz el triaje de la #2 y mira cómo cambia su prioridad', 'enlace' => '/panel/tickets/2'],
                ['texto' => 'Consulta el historial del proyector PRY-003', 'enlace' => ($id = Equipo::where('codigo', 'PRY-003')->value('id')) ? "/panel/equipos/{$id}" : '/panel/equipos'],
            ],
        };
    }
}
