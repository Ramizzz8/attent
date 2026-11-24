<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Adjunto;
use App\Models\Ticket;
use App\Tickets\Adjuntos;
use App\Tickets\EnviarMensaje;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MensajeController extends Controller
{
    public function store(Request $request, int $numero, EnviarMensaje $enviar): RedirectResponse
    {
        $ticket = Ticket::where('numero', $numero)->firstOrFail();
        $datos = $request->validate([
            'cuerpo' => ['required', 'string', 'max:5000'],
            'interno' => ['boolean'],
            ...Adjuntos::reglas(),
        ]);

        $enviar->ejecutar($ticket, $request->user(), $datos['cuerpo'], $datos['adjuntos'] ?? [], (bool) ($datos['interno'] ?? false));

        return back();
    }

    public function adjunto(int $adjunto): StreamedResponse
    {
        // El ticket está filtrado por la empresa del empleado: un adjunto de otra empresa da 404.
        $a = Adjunto::findOrFail($adjunto);
        Ticket::findOrFail($a->ticket_id);

        return Adjuntos::descargar($a);
    }
}
