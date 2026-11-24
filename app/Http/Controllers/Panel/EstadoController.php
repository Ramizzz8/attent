<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Tickets\CambiarEstado;
use App\Tickets\TransicionNoPermitida;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class EstadoController extends Controller
{
    public function store(Request $request, int $numero, CambiarEstado $cambiarEstado): RedirectResponse
    {
        $ticket = Ticket::where('numero', $numero)->firstOrFail();
        $datos = $request->validate([
            'destino' => ['required', 'string'],
            'motivo' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $cambiarEstado->ejecutar($ticket, $datos['destino'], $request->user(), $datos['motivo'] ?? null);
        } catch (TransicionNoPermitida $e) {
            throw ValidationException::withMessages(['destino' => $e->getMessage()]);
        }

        return back();
    }
}
