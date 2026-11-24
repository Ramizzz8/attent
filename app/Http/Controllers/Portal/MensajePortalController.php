<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Adjunto;
use App\Models\Empresa;
use App\Tickets\Adjuntos;
use App\Tickets\EnviarMensaje;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MensajePortalController extends Controller
{
    public function store(Request $request, Empresa $empresa, int $numero, EnviarMensaje $enviar): RedirectResponse
    {
        $ticket = TicketPortalController::ticketDelCliente($numero);
        $datos = $request->validate(['cuerpo' => ['required', 'string', 'max:5000'], ...Adjuntos::reglas()]);

        $enviar->ejecutar($ticket, Auth::guard('cliente')->user(), $datos['cuerpo'], $datos['adjuntos'] ?? []);

        return redirect("/{$empresa->slug}/tickets/{$numero}");
    }

    public function adjunto(Empresa $empresa, int $adjunto): StreamedResponse
    {
        $a = Adjunto::with('mensaje')->findOrFail($adjunto);
        $ticket = TicketPortalController::ticketDelCliente($a->ticket()->value('numero') ?? 0);

        abort_if($a->ticket_id !== $ticket->id || $a->esInterno(), 404);

        return Adjuntos::descargar($a);
    }
}
