<?php

namespace App\Notifications;

use App\Models\Presupuesto;
use App\Portal\EnlacesPortal;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Number;

class PresupuestoEnviado extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Presupuesto $presupuesto) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $t = $this->presupuesto->ticket;
        $empresa = $t->empresa;

        return (new MailMessage)
            ->subject("[#{$t->numero}] Presupuesto para tu vehículo · {$empresa->nombre}")
            ->greeting("Hola, {$notifiable->nombre}")
            ->line('Ya hemos diagnosticado tu vehículo. El presupuesto de la reparación es de **'.Number::currency($this->presupuesto->total(), 'EUR', 'es').'** (IVA incluido).')
            ->line('Puedes ver el detalle y aprobarlo o rechazarlo desde el portal. No empezaremos a reparar sin tu aprobación.')
            ->action('Ver el presupuesto', EnlacesPortal::ticket($t, $notifiable))
            ->salutation($empresa->nombre);
    }
}
