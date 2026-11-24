<?php

namespace App\Portal;

use App\Models\Cliente;
use App\Models\Ticket;
use Illuminate\Support\Facades\URL;

/** URLs firmadas (enlaces mágicos) del portal del cliente. */
final class EnlacesPortal
{
    public static function confirmar(Ticket $ticket): string
    {
        return URL::temporarySignedRoute('portal.confirmar', now()->addDay(), [
            'empresa' => $ticket->empresa->slug,
            'ticket' => $ticket->id,
        ]);
    }

    public static function entrar(Cliente $cliente): string
    {
        return URL::temporarySignedRoute('portal.entrar', now()->addMinutes(30), [
            'empresa' => $cliente->empresa->slug,
            'cliente' => $cliente->id,
        ]);
    }

    /** Enlace incluido en las notificaciones: inicia sesión y abre el ticket. */
    public static function ticket(Ticket $ticket, ?Cliente $cliente = null, int $dias = 7): string
    {
        return URL::temporarySignedRoute('portal.enlace', now()->addDays($dias), [
            'empresa' => $ticket->empresa->slug,
            'cliente' => ($cliente ?? $ticket->cliente)->id,
            'ticket' => $ticket->id,
        ]);
    }
}
