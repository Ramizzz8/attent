<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Adjunto;
use App\Models\Cliente;
use App\Models\Mensaje;
use App\Models\Ticket;
use App\Models\TicketEvento;
use App\Models\User;
use App\Tickets\CambiarEstado;
use Inertia\Inertia;
use Inertia\Response;

class TicketController extends Controller
{
    public function show(int $numero, CambiarEstado $cambiarEstado): Response
    {
        $ticket = Ticket::where('numero', $numero)->with(['cliente', 'asignado'])->firstOrFail();
        $sector = $ticket->sector();
        $estados = $sector->estados();

        return Inertia::render('panel/ticket', [
            'ticket' => [
                'id' => $ticket->id,
                'numero' => $ticket->numero,
                'asunto' => $ticket->asunto,
                'descripcion' => $ticket->descripcion,
                'categoria' => $sector->categorias()[$ticket->categoria] ?? $ticket->categoria,
                'estado' => $ticket->estado,
                'estadoEtiqueta' => $ticket->etiquetaEstado(),
                'prioridad' => $ticket->prioridad->value,
                'datos' => $ticket->datos ?? [],
                'verificado' => $ticket->verificado_en !== null,
                'creado' => $ticket->created_at->toIso8601String(),
                'sla' => $ticket->estadoSla(),
                'slaVence' => $ticket->sla_vence_en?->toIso8601String(),
                'primeraRespuestaVence' => $ticket->primera_respuesta_vence_en?->toIso8601String(),
                'primeraRespuesta' => $ticket->primera_respuesta_en?->toIso8601String(),
                'cliente' => $ticket->cliente->only(['nombre', 'email', 'telefono']),
                'asignadoId' => $ticket->asignado_a,
                'adjuntos' => $ticket->adjuntos()->whereNull('mensaje_id')->get()->map(fn (Adjunto $a) => $a->resumen()),
                'mensajes' => $ticket->mensajes()->with(['adjuntos', 'autor'])->orderBy('id')->get()->map(fn (Mensaje $m) => [
                    'id' => $m->id,
                    'cuerpo' => $m->cuerpo,
                    'interno' => $m->interno,
                    'delEquipo' => $m->esDelEquipo(),
                    'autor' => $m->autor instanceof User ? $m->autor->name : ($m->autor?->nombre ?? 'Desconocido'),
                    'fecha' => $m->created_at->toIso8601String(),
                    'adjuntos' => $m->adjuntos->map(fn (Adjunto $a) => $a->resumen()),
                ]),
                'historial' => $ticket->eventos()->with(['user', 'cliente'])->orderByDesc('id')->get()->map(fn (TicketEvento $e) => [
                    'id' => $e->id,
                    'tipo' => $e->tipo,
                    'de' => $e->de ? ($estados[$e->de] ?? $e->de) : null,
                    'a' => $e->a ? ($estados[$e->a] ?? $e->a) : null,
                    'motivo' => $e->motivo,
                    'datos' => $e->datos,
                    'actor' => $e->user?->name ?? ($e->cliente instanceof Cliente ? $e->cliente->nombre.' (cliente)' : 'Sistema'),
                    'fecha' => $e->created_at->toIso8601String(),
                ]),
                ...ModulosTicket::datos($ticket),
            ],
            'destinos' => $cambiarEstado->destinosPosibles($ticket),
            'empleados' => User::where('empresa_id', $ticket->empresa_id)->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
