<?php

namespace App\Notifications;

use App\Models\Ticket;
use App\Portal\EnlacesPortal;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ConfirmarTicket extends Notification implements ShouldQueue
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
        $empresa = $this->ticket->empresa;

        return (new MailMessage)
            ->subject("Confirma tu incidencia en {$empresa->nombre}")
            ->greeting("Hola, {$notifiable->nombre}")
            ->line("Hemos recibido tu incidencia «{$this->ticket->asunto}».")
            ->line('Para que llegue al equipo, confirma que este email es tuyo:')
            ->action('Confirmar incidencia', EnlacesPortal::confirmar($this->ticket))
            ->line('El enlace caduca en 24 horas. Si no has sido tú, ignora este mensaje.')
            ->salutation($empresa->nombre);
    }
}
