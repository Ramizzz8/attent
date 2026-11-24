<?php

namespace App\Notifications;

use App\Portal\EnlacesPortal;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EnlaceAcceso extends Notification implements ShouldQueue
{
    use Queueable;

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $empresa = $notifiable->empresa;

        return (new MailMessage)
            ->subject("Tu enlace de acceso a {$empresa->nombre}")
            ->greeting("Hola, {$notifiable->nombre}")
            ->line('Pulsa el botón para ver tus incidencias. No necesitas contraseña.')
            ->action('Ver mis incidencias', EnlacesPortal::entrar($notifiable))
            ->line('El enlace caduca en 30 minutos.')
            ->salutation($empresa->nombre);
    }
}
