<?php

namespace App\Listeners;

use App\Events\MensajeCreado;
use App\Events\TicketEstadoCambiado;
use App\Models\Cliente;
use App\Models\User;
use App\Notifications\EstadoCambiado;
use App\Notifications\NuevoMensaje;
use Illuminate\Support\Facades\Notification;

/** Envía los emails a clientes y empleados cuando cambia un ticket. */
class AvisarPorEmail
{
    /** Estados de sistema que ya tienen su propio email (p. ej. el presupuesto). */
    private const CON_EMAIL_PROPIO = ['presupuesto_enviado'];

    public function estadoCambiado(TicketEstadoCambiado $evento): void
    {
        $t = $evento->ticket;

        if ($t->verificado_en === null || in_array($t->estado, self::CON_EMAIL_PROPIO, true)) {
            return;
        }

        $destinatarios = $t->clientesAvisables()
            ->reject(fn (Cliente $c) => $evento->actor instanceof Cliente && $evento->actor->is($c));

        Notification::send($destinatarios, new EstadoCambiado($t));
    }

    public function mensajeCreado(MensajeCreado $evento): void
    {
        $m = $evento->mensaje;
        $t = $m->ticket;

        if ($m->interno || $t->verificado_en === null) {
            return;
        }

        if ($m->autor instanceof Cliente) {
            if ($t->asignado instanceof User) {
                $t->asignado->notify(new NuevoMensaje($m));
            }

            return;
        }

        Notification::send($t->clientesAvisables(), new NuevoMensaje($m));
    }
}
