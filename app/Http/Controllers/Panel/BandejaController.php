<?php

namespace App\Http\Controllers\Panel;

use App\Enums\Prioridad;
use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\User;
use App\Tenancy\EmpresaActual;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BandejaController extends Controller
{
    public function index(Request $request, EmpresaActual $empresaActual): Response
    {
        $empresa = $empresaActual->get();
        $sector = $empresa->motor();
        $filtros = $request->only(['estado', 'prioridad', 'asignado', 'categoria', 'sla', 'q', 'edificio_id']);

        $query = Ticket::query()->verificados()->with(['cliente', 'asignado']);

        $estado = $filtros['estado'] ?? 'abiertos';
        match (true) {
            $estado === 'abiertos' => $query->abiertos($sector),
            $estado === 'todos' => null,
            default => $query->where('estado', $estado),
        };

        $query->when($filtros['prioridad'] ?? null, fn (Builder $q, $p) => $q->where('prioridad', $p));
        $query->when($filtros['categoria'] ?? null, fn (Builder $q, $c) => $q->where('categoria', $c));
        $query->when($filtros['edificio_id'] ?? null, fn (Builder $q, $e) => $q->where('edificio_id', $e));
        $query->when($filtros['asignado'] ?? null, fn (Builder $q, $a) => match ($a) {
            'yo' => $q->where('asignado_a', $request->user()->id),
            'sin_asignar' => $q->whereNull('asignado_a'),
            default => $q->where('asignado_a', (int) $a),
        });
        $query->when($filtros['sla'] ?? null, fn (Builder $q, $s) => $this->filtrarSla($q, $s, $empresa));
        $query->when($filtros['q'] ?? null, fn (Builder $q, $texto) => $this->buscar($q, trim($texto)));

        $tickets = $query
            ->orderByRaw('sla_vence_en is null')
            ->orderBy('sla_vence_en')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString()
            ->through(fn (Ticket $t) => [
                'numero' => $t->numero,
                'asunto' => $t->asunto,
                'cliente' => $t->cliente->nombre,
                'estado' => $t->estado,
                'estadoEtiqueta' => $t->etiquetaEstado(),
                'prioridad' => $t->prioridad->value,
                'categoria' => $sector->categorias()[$t->categoria] ?? $t->categoria,
                'asignado' => $t->asignado?->name,
                'sla' => $t->estadoSla(),
                'slaVence' => $t->sla_vence_en?->toIso8601String(),
                'creado' => $t->created_at->toIso8601String(),
            ]);

        return Inertia::render('panel/bandeja', [
            'tickets' => $tickets,
            'filtros' => (object) $filtros,
            'opciones' => [
                'estados' => collect($sector->estados())->map(fn ($e, $k) => ['valor' => $k, 'etiqueta' => $e])->values(),
                'categorias' => collect($sector->categorias())->map(fn ($e, $k) => ['valor' => $k, 'etiqueta' => $e])->values(),
                'prioridades' => collect(Prioridad::cases())->map(fn ($p) => ['valor' => $p->value, 'etiqueta' => $p->etiqueta()]),
                'empleados' => User::where('empresa_id', $empresa->id)->orderBy('name')->get(['id', 'name'])
                    ->map(fn ($u) => ['valor' => (string) $u->id, 'etiqueta' => $u->name]),
                ...OpcionesSector::bandeja($empresa),
            ],
        ]);
    }

    private function filtrarSla(Builder $q, string $filtro, $empresa): void
    {
        $q->whereNull('sla_cumplido')->whereNull('sla_pausado_en')->whereNotNull('sla_vence_en');

        if ($filtro === 'vencido') {
            $q->where('sla_vence_en', '<', now());

            return;
        }

        if ($filtro === 'en_riesgo') {
            // En riesgo: queda menos del 20 % del plazo, que depende de la prioridad.
            $q->where('sla_vence_en', '>=', now())->where(function (Builder $q) use ($empresa) {
                foreach (Prioridad::cases() as $p) {
                    $margen = (int) round($empresa->motor()->slaMinutos($p, $empresa) * 0.2 * 60);
                    $q->orWhere(fn (Builder $q) => $q->where('prioridad', $p->value)->where('sla_vence_en', '<', now()->addSeconds($margen)));
                }
            });
        }
    }

    private function buscar(Builder $q, string $texto): void
    {
        $q->where(function (Builder $q) use ($texto) {
            if (ctype_digit($texto)) {
                $q->orWhere('numero', (int) $texto);
            }
            $like = '%'.mb_strtolower($texto).'%';
            $q->orWhereRaw('lower(asunto) like ?', [$like])
                ->orWhereHas('cliente', fn (Builder $c) => $c->whereRaw('lower(nombre) like ?', [$like])->orWhereRaw('lower(email) like ?', [$like]));
            OpcionesSector::buscar($q, $like);
        });
    }
}
