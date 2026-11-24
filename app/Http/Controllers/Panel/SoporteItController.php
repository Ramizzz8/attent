<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Equipo;
use App\Models\Ticket;
use App\SoporteIt\MatrizPrioridad;
use App\Tenancy\EmpresaActual;
use App\Tickets\Sla;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/** Soporte informático: inventario de equipos y triaje de urgencia. */
class SoporteItController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('panel/soporte-it/equipos', [
            'equipos' => Equipo::withCount('tickets')->orderBy('codigo')->get()->map(fn (Equipo $e) => [
                'id' => $e->id,
                'codigo' => $e->codigo,
                'tipo' => Equipo::TIPOS[$e->tipo] ?? $e->tipo,
                'ubicacion' => $e->ubicacion,
                'incidencias' => $e->tickets_count,
            ]),
            'tipos' => collect(Equipo::TIPOS)->map(fn ($e, $k) => ['valor' => $k, 'etiqueta' => $e])->values(),
        ]);
    }

    public function show(int $equipo): Response
    {
        $e = Equipo::findOrFail($equipo);

        return Inertia::render('panel/soporte-it/equipo', [
            'equipo' => [
                'id' => $e->id,
                'codigo' => $e->codigo,
                'tipo' => Equipo::TIPOS[$e->tipo] ?? $e->tipo,
                'ubicacion' => $e->ubicacion,
                'notas' => $e->notas,
                'enlace' => url('/'.app(EmpresaActual::class)->get()->slug.'/nueva?equipo='.urlencode($e->codigo)),
            ],
            'tickets' => $e->tickets()->latest()->get()->map(fn (Ticket $t) => [
                'numero' => $t->numero,
                'asunto' => $t->asunto,
                'estado' => $t->etiquetaEstado(),
                'prioridad' => $t->prioridad->value,
                'creado' => $t->created_at->toIso8601String(),
            ]),
        ]);
    }

    public function store(Request $request, EmpresaActual $empresaActual): RedirectResponse
    {
        $request->merge(['codigo' => Equipo::normalizarCodigo((string) $request->input('codigo'))]);
        $datos = $request->validate([
            'codigo' => ['required', 'string', 'max:30', Rule::unique('equipos')->where('empresa_id', $empresaActual->id())],
            'tipo' => ['required', Rule::in(array_keys(Equipo::TIPOS))],
            'ubicacion' => ['required', 'string', 'max:100'],
            'notas' => ['nullable', 'string', 'max:1000'],
        ], ['codigo.unique' => 'Ya existe un equipo con ese código.']);

        Equipo::create($datos);

        return back()->with('status', 'Equipo añadido.');
    }

    /** El técnico fija la urgencia; la prioridad sale de la matriz impacto × urgencia. */
    public function triaje(Request $request, int $numero, Sla $sla): RedirectResponse
    {
        $ticket = Ticket::where('numero', $numero)->firstOrFail();
        $datos = $request->validate(['urgencia' => ['required', Rule::in(MatrizPrioridad::URGENCIAS)]]);

        $anterior = $ticket->prioridad;
        $ticket->urgencia = $datos['urgencia'];
        $ticket->prioridad = MatrizPrioridad::calcular($ticket->impacto ?? 'individual', $datos['urgencia']);

        if ($ticket->prioridad !== $anterior) {
            $sla->recalcular($ticket);
            $ticket->eventos()->create([
                'tipo' => 'prioridad',
                'user_id' => $request->user()->id,
                'datos' => ['de' => $anterior->etiqueta(), 'a' => $ticket->prioridad->etiqueta(), 'motivo' => "Urgencia {$datos['urgencia']}"],
            ]);
        }
        $ticket->save();

        return back()->with('exito', "Urgencia actualizada: prioridad {$ticket->prioridad->etiqueta()}.");
    }
}
