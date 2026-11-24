<?php

namespace App\Tickets;

use App\Events\MensajeCreado;
use App\Models\Cliente;
use App\Models\Mensaje;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class EnviarMensaje
{
    public function __construct(private CambiarEstado $cambiarEstado) {}

    /** @param list<UploadedFile> $archivos */
    public function ejecutar(Ticket $ticket, Model $autor, string $cuerpo, array $archivos = [], bool $interno = false): Mensaje
    {
        // Solo el equipo puede escribir notas internas.
        $interno = $interno && $autor instanceof User;

        $mensaje = DB::transaction(function () use ($ticket, $autor, $cuerpo, $archivos, $interno) {
            $mensaje = $ticket->mensajes()->make(['cuerpo' => trim($cuerpo), 'interno' => $interno]);
            $mensaje->autor()->associate($autor);
            $mensaje->save();

            Adjuntos::guardar($ticket, $mensaje, $archivos);

            if ($autor instanceof User && ! $interno && $ticket->primera_respuesta_en === null) {
                $ticket->forceFill(['primera_respuesta_en' => now()])->save();
            }

            return $mensaje;
        });

        // Si el ticket esperaba al cliente y este responde, vuelve a la cola de trabajo.
        if ($autor instanceof Cliente && $ticket->fresh()->estado === 'pendiente_usuario') {
            $this->cambiarEstado->ejecutar($ticket, 'en_curso', $autor, sistema: true);
        }

        MensajeCreado::dispatch($mensaje);

        return $mensaje->load('adjuntos');
    }
}
