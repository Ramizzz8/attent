<?php

namespace App\Fincas;

use App\Models\Ticket;
use Illuminate\Support\Facades\URL;

/** URLs firmadas (30 días) con las que el proveedor gestiona su incidencia sin cuenta. */
final class EnlacesProveedor
{
    public const DIAS = 30;

    public static function ver(Ticket $ticket): string
    {
        return self::firmar('proveedor.ticket', $ticket);
    }

    public static function visita(Ticket $ticket): string
    {
        return self::firmar('proveedor.visita', $ticket);
    }

    public static function resolver(Ticket $ticket): string
    {
        return self::firmar('proveedor.resolver', $ticket);
    }

    private static function firmar(string $ruta, Ticket $ticket): string
    {
        return URL::temporarySignedRoute($ruta, now()->addDays(self::DIAS), [
            'proveedor' => $ticket->proveedor_id,
            'ticket' => $ticket->id,
        ]);
    }
}
