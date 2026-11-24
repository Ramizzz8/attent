<?php

namespace App\Http\Controllers\Panel;

use App\Models\Ticket;

/** Datos de los módulos propios del sector que se muestran en el detalle del ticket. */
final class ModulosTicket
{
    /** @return array<string, mixed> */
    public static function datos(Ticket $ticket): array
    {
        return [];
    }
}
