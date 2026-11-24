<?php

namespace App\Http\Controllers\Proveedor;

use App\Fincas\EnlacesProveedor;
use App\Http\Controllers\Controller;
use App\Models\Proveedor;
use App\Models\Ticket;
use App\Tenancy\EmpresaActual;
use App\Tickets\Adjuntos;
use App\Tickets\CambiarEstado;
use App\Tickets\EnviarMensaje;
use App\Tickets\TransicionNoPermitida;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/** Página del proveedor externo, a la que entra con un enlace firmado (sin cuenta). */
class ProveedorTicketController extends Controller
{
    public function show(int $proveedor, int $ticket): Response
    {
        [$t, $p] = $this->cargar($proveedor, $ticket);

        return Inertia::render('proveedor/ticket', [
            'empresa' => ['nombre' => $t->empresa->nombre, 'color' => $t->empresa->color_primario],
            'proveedor' => $p->nombre,
            'ticket' => [
                'numero' => $t->numero,
                'asunto' => $t->asunto,
                'descripcion' => $t->descripcion,
                'categoria' => $t->sector()->categorias()[$t->categoria] ?? $t->categoria,
                'estado' => $t->estado,
                'estadoEtiqueta' => $t->etiquetaEstado(),
                'edificio' => $t->edificio->nombre,
                'direccion' => $t->edificio->direccion,
                'zona' => $t->zona?->nombre,
                'vivienda' => $t->datos['vivienda'] ?? null,
                'contacto' => $t->cliente->only(['nombre', 'telefono']),
                'visita' => $t->visita_programada_en?->toIso8601String(),
            ],
            'acciones' => [
                'visita' => in_array($t->estado, ['asignada_proveedor', 'visita_programada'], true) ? EnlacesProveedor::visita($t) : null,
                'resolver' => in_array($t->estado, ['asignada_proveedor', 'visita_programada'], true) ? EnlacesProveedor::resolver($t) : null,
            ],
        ]);
    }

    public function visita(Request $request, int $proveedor, int $ticket, CambiarEstado $cambiarEstado): RedirectResponse
    {
        [$t] = $this->cargar($proveedor, $ticket);
        $datos = $request->validate(['fecha' => ['required', 'date', 'after:now']], ['fecha.after' => 'La visita tiene que ser en el futuro.']);

        $t->update(['visita_programada_en' => $datos['fecha']]);
        if ($t->estado === 'asignada_proveedor') {
            $this->cambiar($cambiarEstado, $t, 'visita_programada');
        }

        return back()->with('status', 'Visita programada.');
    }

    public function resolver(Request $request, int $proveedor, int $ticket, EnviarMensaje $enviar, CambiarEstado $cambiarEstado): RedirectResponse
    {
        [$t, $p] = $this->cargar($proveedor, $ticket);
        $datos = $request->validate(['comentario' => ['required', 'string', 'max:2000'], ...Adjuntos::reglas()]);
        if (! in_array($t->estado, ['asignada_proveedor', 'visita_programada'], true)) {
            throw ValidationException::withMessages(['comentario' => 'Esta incidencia ya está '.mb_strtolower($t->etiquetaEstado()).'.']);
        }

        $enviar->ejecutar($t, $p, $datos['comentario'], $datos['adjuntos'] ?? []);
        $this->cambiar($cambiarEstado, $t, 'resuelta');

        return back()->with('status', 'Incidencia marcada como resuelta. ¡Gracias!');
    }

    /** @return array{0: Ticket, 1: Proveedor} */
    private function cargar(int $proveedor, int $ticket): array
    {
        $t = Ticket::withoutGlobalScopes()->findOrFail($ticket);
        // Si la incidencia se reasignó a otro proveedor, el enlace antiguo deja de servir.
        abort_unless($t->proveedor_id === $proveedor, 403, 'Esta incidencia ya no está asignada a ti.');
        app(EmpresaActual::class)->set($t->empresa);

        return [$t, Proveedor::findOrFail($proveedor)];
    }

    private function cambiar(CambiarEstado $cambiarEstado, Ticket $t, string $destino): void
    {
        try {
            $cambiarEstado->ejecutar($t, $destino, null);
        } catch (TransicionNoPermitida $e) {
            throw ValidationException::withMessages(['comentario' => $e->getMessage()]);
        }
    }
}
