<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Taller\EnviarPresupuesto;
use App\Tickets\TransicionNoPermitida;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PresupuestoController extends Controller
{
    public function pdf(int $numero, int $version): \Symfony\Component\HttpFoundation\Response
    {
        return \App\Taller\PdfPresupuesto::descargar(Ticket::where('numero', $numero)->firstOrFail(), $version);
    }

    public function store(Request $request, int $numero, EnviarPresupuesto $enviar): RedirectResponse
    {
        $ticket = Ticket::where('numero', $numero)->firstOrFail();
        $datos = $request->validate([
            'iva_pct' => ['required', 'numeric', 'min:0', 'max:30'],
            'lineas' => ['required', 'array', 'min:1', 'max:50'],
            'lineas.*.tipo' => ['required', 'in:pieza,mano_obra'],
            'lineas.*.concepto' => ['required', 'string', 'max:150'],
            'lineas.*.cantidad' => ['required', 'numeric', 'gt:0', 'max:9999'],
            'lineas.*.precio_unitario' => ['required', 'numeric', 'min:0', 'max:999999'],
        ]);

        try {
            $enviar->ejecutar($ticket, $datos['lineas'], (float) $datos['iva_pct'], $request->user());
        } catch (TransicionNoPermitida $e) {
            throw ValidationException::withMessages(['lineas' => $e->getMessage()]);
        }

        return back()->with('exito', 'Presupuesto enviado al cliente.');
    }
}
