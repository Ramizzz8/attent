<?php

namespace App\Notifications;

use App\Models\Cliente;
use App\Models\Mensaje;
use App\Portal\EnlacesPortal;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class NuevoMensaje extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Mensaje $mensaje) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $t = $this->mensaje->ticket;
        $empresa = $t->empresa;
        $esCliente = $notifiable instanceof Cliente;

        return (new MailMessage)
            ->subject("[#{$t->numero}] Nuevo mensaje · {$empresa->nombre}")
            ->greeting('Hola, '.($esCliente ? $notifiable->nombre : $notifiable->name))
            ->line($esCliente ? "Hay una respuesta en tu incidencia «{$t->asunto}»:" : "El cliente ha respondido en la incidencia #{$t->numero}:")
            ->line('> '.Str::limit($this->mensaje->cuerpo, 300))
            ->action(
                $esCliente ? 'Ver y responder' : 'Abrir en el panel',
                $esCliente ? EnlacesPortal::ticket($t, $notifiable) : url("/panel/tickets/{$t->numero}"),
            )
            ->salutation($empresa->nombre);
    }
}
