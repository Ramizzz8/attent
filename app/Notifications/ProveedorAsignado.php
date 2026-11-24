<?php

namespace App\Notifications;

use App\Fincas\EnlacesProveedor;
use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProveedorAsignado extends Notification implements ShouldQueue
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
        $edificio = $t->edificio;

        return (new MailMessage)
            ->subject("Nueva incidencia asignada: {$t->asunto} ({$edificio->nombre})")
            ->greeting("Hola, {$notifiable->nombre}")
            ->line("{$t->empresa->nombre} te ha asignado una incidencia en **{$edificio->nombre}** ({$edificio->direccion}), zona: {$t->zona?->nombre}.")
            ->line("«{$t->asunto}»")
            ->line('Desde el enlace puedes ver los detalles, programar la visita y marcarla como resuelta. No necesitas cuenta.')
            ->action('Gestionar la incidencia', EnlacesProveedor::ver($t))
            ->line('El enlace es válido durante '.EnlacesProveedor::DIAS.' días.')
            ->salutation($t->empresa->nombre);
    }
}
