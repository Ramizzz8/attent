<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class ConfirmarAfectado extends Notification implements ShouldQueue
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
        $url = URL::temporarySignedRoute('portal.afectado.confirmar', now()->addDay(), [
            'empresa' => $t->empresa->slug,
            'ticket' => $t->id,
            'cliente' => $notifiable->id,
        ]);

        return (new MailMessage)
            ->subject("Confirma que también te afecta: {$t->asunto}")
            ->greeting("Hola, {$notifiable->nombre}")
            ->line("Has indicado que la incidencia «{$t->asunto}» también te afecta.")
            ->line('Confírmalo para que contemos tu aviso y te mantengamos informado:')
            ->action('Confirmar', $url)
            ->line('El enlace caduca en 24 horas.')
            ->salutation($t->empresa->nombre);
    }
}
