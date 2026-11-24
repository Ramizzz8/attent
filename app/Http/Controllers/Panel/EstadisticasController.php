<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\TicketEvento;
use App\Models\User;
use App\Tenancy\EmpresaActual;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

/** Indicadores de la empresa en un rango de fechas (por defecto, los últimos 30 días). */
class EstadisticasController extends Controller
{
    public function index(Request $request, EmpresaActual $empresaActual): Response
    {
        $empresa = $empresaActual->get();
        $sector = $empresa->motor();
        $datos = $request->validate([
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date'],
            'edificio_id' => ['nullable', 'integer'],
        ]);
        $desde = Carbon::parse($datos['desde'] ?? now()->subDays(30))->startOfDay();
        $hasta = Carbon::parse($datos['hasta'] ?? now())->endOfDay();

        $tickets = Ticket::query()
            ->verificados()
            ->whereBetween('created_at', [$desde, $hasta])
            ->when($datos['edificio_id'] ?? null, fn ($q, $e) => $q->where('edificio_id', $e))
            ->get();

        $terminados = $tickets->filter(fn (Ticket $t) => in_array($t->estado, $sector->estadosTerminados(), true));
        $evaluados = $tickets->whereNotNull('sla_cumplido');
        $valorados = $tickets->whereNotNull('valoracion');

        // Tiempo hasta el estado objetivo del sector (diagnóstico en el taller, resolución en el resto).
        $llegadas = TicketEvento::query()
            ->whereIn('ticket_id', $tickets->pluck('id'))
            ->where('tipo', 'estado')
            ->where('a', $sector->estadoObjetivoSla())
            ->orderBy('id')
            ->get()
            ->unique('ticket_id');
        $horas = $llegadas->map(function (TicketEvento $e) use ($tickets) {
            $t = $tickets->firstWhere('id', $e->ticket_id);

            return $t->verificado_en->diffInMinutes($e->created_at) / 60;
        });

        $empleados = User::where('empresa_id', $empresa->id)->pluck('name', 'id');

        return Inertia::render('panel/estadisticas', [
            'filtros' => ['desde' => $desde->toDateString(), 'hasta' => $hasta->toDateString(), 'edificio_id' => $datos['edificio_id'] ?? null],
            'resumen' => [
                'total' => $tickets->count(),
                'abiertos' => $tickets->count() - $terminados->count(),
                'terminados' => $terminados->count(),
                'slaCumplido' => $evaluados->isEmpty() ? null : (int) round(100 * $evaluados->where('sla_cumplido', true)->count() / $evaluados->count()),
                'horasMedias' => $horas->isEmpty() ? null : round($horas->avg(), 1),
                'satisfaccion' => $valorados->isEmpty() ? null : round($valorados->avg('valoracion'), 1),
                'valoraciones' => $valorados->count(),
            ],
            'porEmpleado' => $tickets->whereNotNull('asignado_a')->groupBy('asignado_a')
                ->map(fn ($grupo, $id) => [
                    'nombre' => $empleados[$id] ?? 'Antiguo empleado',
                    'total' => $grupo->count(),
                    'abiertos' => $grupo->reject(fn ($t) => in_array($t->estado, $sector->estadosTerminados(), true))->count(),
                ])
                ->sortByDesc('total')->values(),
            'porCategoria' => $tickets->groupBy('categoria')
                ->map(fn ($grupo, $c) => ['etiqueta' => $sector->categorias()[$c] ?? $c, 'total' => $grupo->count()])
                ->sortByDesc('total')->values(),
            'porPrioridad' => $tickets->groupBy(fn (Ticket $t) => $t->prioridad->value)
                ->map(fn ($grupo, $p) => ['etiqueta' => ucfirst($p), 'total' => $grupo->count()])->values(),
            'porSemana' => $this->porSemana($tickets, $desde, $hasta),
            'opciones' => OpcionesSector::bandeja($empresa),
        ]);
    }

    /**
     * Recuento por semana incluyendo las semanas sin incidencias (a cero), para no falsear el eje.
     *
     * @return list<array{semana: string, total: int}>
     */
    private function porSemana($tickets, Carbon $desde, Carbon $hasta): array
    {
        if ($tickets->isEmpty()) {
            return [];
        }

        $cuenta = $tickets->countBy(fn (Ticket $t) => $t->created_at->copy()->startOfWeek()->toDateString());
        $semanas = [];
        for ($s = $desde->copy()->startOfWeek(); $s->lessThanOrEqualTo($hasta); $s->addWeek()) {
            $semanas[] = ['semana' => $s->toDateString(), 'total' => $cuenta[$s->toDateString()] ?? 0];
        }

        return $semanas;
    }
}
