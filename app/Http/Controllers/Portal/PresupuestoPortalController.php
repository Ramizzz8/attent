<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Taller\ResponderPresupuesto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class PresupuestoPortalController extends Controller
{
    public function store(Request $request, Empresa $empresa, int $numero, ResponderPresupuesto $responder): RedirectResponse
    {
        $ticket = TicketPortalController::ticketDelCliente($numero);
        $datos = $request->validate([
            'aprueba' => ['required', 'boolean'],
            'comentario' => ['nullable', 'string', 'max:500'],
        ]);

        $presupuesto = $ticket->presupuestoVigente()
            ?? throw ValidationException::withMessages(['presupuesto' => 'Esta incidencia no tiene presupuesto.']);

        $responder->ejecutar($presupuesto, Auth::guard('cliente')->user(), (bool) $datos['aprueba'], $datos['comentario'] ?? null);

        return redirect("/{$empresa->slug}/tickets/{$numero}");
    }
}
