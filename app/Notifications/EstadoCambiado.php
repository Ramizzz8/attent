<?php

namespace App\Notifications;

use App\Models\Ticket;
use App\Portal\EnlacesPortal;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EstadoCambiado extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Ticket $ticket) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $t = $this->ticket;
        $empresa = $t->empresa;
        $estado = $t->etiquetaEstado();

        $mail = (new MailMessage)
            ->subject("[#{$t->numero}] {$estado} · {$empresa->nombre}")
            ->greeting("Hola, {$notifiable->nombre}")
            ->line("Tu incidencia «{$t->asunto}» ha pasado a: **{$estado}**.");

        if ($t->estado === $t->sector()->estadoResuelto()) {
            $mail->line('Si el problema persiste, puedes reabrirla durante los próximos 7 días. También nos ayudará mucho que valores la atención.');
        }

        return $mail
            ->action('Ver la incidencia', EnlacesPortal::ticket($t, $notifiable))
            ->salutation($empresa->nombre);
    }
}
