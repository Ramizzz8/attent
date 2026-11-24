<?php

namespace App\Http\Controllers\Portal;

use App\Enums\SectorClave;
use App\Fincas\BuscarDuplicado;
use App\Fincas\SumarAfectado;
use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Edificio;
use App\Models\Empresa;
use App\Models\Ticket;
use App\Notifications\ConfirmarAfectado;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

/** Fincas: incidencias repetidas y "a mí también me afecta". */
class AfectadoController extends Controller
{
    /** Formulario con el edificio ya elegido (enlace o QR del propio edificio). */
    public function edificio(Request $request, Empresa $empresa, string $edificio): Response
    {
        $e = Edificio::where('slug', $edificio)->firstOrFail();

        return Inertia::render('portal/nueva', [
            'categorias' => collect($empresa->motor()->categorias())
                ->map(fn ($etiqueta, $valor) => ['valor' => $valor, 'etiqueta' => $etiqueta])->values(),
            ...PortalFormularioSector::datos($empresa, $request),
            'valoresIniciales' => ['edificio_id' => $e->id],
        ]);
    }

    public function duplicados(Request $request, BuscarDuplicado $buscar): JsonResponse
    {
        $datos = $request->validate([
            'edificio_id' => ['required', 'integer'],
            'zona_id' => ['required', 'integer'],
            'categoria' => ['required', 'string'],
        ]);
        $t = $buscar->ejecutar($datos['edificio_id'], $datos['zona_id'], $datos['categoria']);

        return response()->json(['ticket' => $t ? [
            'numero' => $t->numero,
            'asunto' => $t->asunto,
            'estado' => $t->etiquetaEstado(),
            'creado' => $t->created_at->toIso8601String(),
            'afectados' => 1 + $t->afectados()->count(),
        ] : null]);
    }

    public function store(Request $request, Empresa $empresa, int $numero, SumarAfectado $sumar): RedirectResponse
    {
        $ticket = $this->incidenciaAbiertaDeFincas($empresa, Ticket::verificados()->where('numero', $numero));
        /** @var Cliente|null $cliente */
        $cliente = Auth::guard('cliente')->user();

        if ($cliente) {
            $sumar->ejecutar($ticket, $cliente);

            return redirect("/{$empresa->slug}/tickets/{$numero}");
        }

        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150'],
        ]);
        $cliente = Cliente::firstOrCreate(['email' => mb_strtolower($datos['email'])], ['nombre' => $datos['nombre']]);
        $cliente->notify(new ConfirmarAfectado($ticket));

        return redirect("/{$empresa->slug}/pendiente");
    }

    /**
     * "A mí también me afecta" solo existe en el administrador de fincas y solo para incidencias
     * abiertas: si no, cualquiera podría sumarse a un ticket ajeno y leerlo.
     */
    private function incidenciaAbiertaDeFincas(Empresa $empresa, $consulta): Ticket
    {
        abort_unless($empresa->sector === SectorClave::Fincas, 404);

        return $consulta->verificados()->abiertos($empresa->motor())->firstOrFail();
    }

    public function confirmar(Request $request, Empresa $empresa, int $ticket, int $cliente, SumarAfectado $sumar): RedirectResponse
    {
        $t = $this->incidenciaAbiertaDeFincas($empresa, Ticket::whereKey($ticket));
        $c = Cliente::findOrFail($cliente);

        $sumar->ejecutar($t, $c);
        $c->email_verificado_en ??= now();
        $c->save();
        Auth::guard('cliente')->login($c, remember: true);
        $request->session()->regenerate();

        return redirect("/{$empresa->slug}/tickets/{$t->numero}");
    }
}
