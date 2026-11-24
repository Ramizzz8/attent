<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\User;
use App\Sectores\SoporteIt;
use App\Tickets\CambiarEstado;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AsignacionController extends Controller
{
    public function store(Request $request, int $numero, CambiarEstado $cambiarEstado): RedirectResponse
    {
        $ticket = Ticket::where('numero', $numero)->firstOrFail();
        $datos = $request->validate([
            'user_id' => ['nullable', 'integer', Rule::exists('users', 'id')->where('empresa_id', $ticket->empresa_id)],
        ], ['user_id.exists' => 'Ese empleado no pertenece a tu empresa.']);

        $nuevo = $datos['user_id'] ?? null;
        if ($nuevo === $ticket->asignado_a) {
            return back();
        }
        $mensaje = $nuevo ? 'Incidencia asignada a '.User::find($nuevo)->name.'.' : 'Asignación quitada.';

        $anterior = $ticket->asignado?->name;
        $ticket->update(['asignado_a' => $nuevo]);
        $ticket->eventos()->create([
            'tipo' => 'asignado',
            'user_id' => $request->user()->id,
            'datos' => ['de' => $anterior, 'a' => $nuevo ? User::find($nuevo)->name : null],
        ]);

        // En soporte informático, asignar un ticket nuevo lo pasa directamente a "Asignado".
        if ($nuevo && $ticket->estado === 'nuevo' && $ticket->sector() instanceof SoporteIt) {
            $cambiarEstado->ejecutar($ticket, 'asignado', $request->user());
        }

        return back()->with('exito', $mensaje);
    }
}
