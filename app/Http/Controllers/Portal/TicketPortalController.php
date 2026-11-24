<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\Ticket;
use App\Notifications\ConfirmarTicket;
use App\Portal\EnlacesPortal;
use App\Tickets\Adjuntos;
use App\Tickets\CrearTicket;
use App\Tickets\ReabrirTicket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class TicketPortalController extends Controller
{
    public function create(Request $request, Empresa $empresa): Response
    {
        return Inertia::render('portal/nueva', array_filter([
            'categorias' => collect($empresa->motor()->categorias())
                ->map(fn ($etiqueta, $valor) => ['valor' => $valor, 'etiqueta' => $etiqueta])->values(),
            ...PortalFormularioSector::datos($empresa, $request),
        ], fn ($v) => $v !== null));
    }

    public function store(Request $request, Empresa $empresa, CrearTicket $crear): RedirectResponse
    {
        /** @var Cliente|null $cliente */
        $cliente = Auth::guard('cliente')->user();
        $sector = $empresa->motor();

        $validado = $request->validate([
            'nombre' => [Rule::requiredIf($cliente === null), 'nullable', 'string', 'max:100'],
            'email' => [Rule::requiredIf($cliente === null), 'nullable', 'email', 'max:150'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'asunto' => ['required', 'string', 'max:150'],
            'descripcion' => ['required', 'string', 'max:5000'],
            'categoria' => ['required', Rule::in(array_keys($sector->categorias()))],
            ...$sector->reglasFormulario(),
            ...Adjuntos::reglas(),
        ]);

        if ($cliente === null) {
            $cliente = Cliente::firstOrCreate(
                ['email' => mb_strtolower($validado['email'])],
                ['nombre' => $validado['nombre'], 'telefono' => $validado['telefono'] ?? null],
            );
        }

        $ticket = $crear->ejecutar($empresa, $cliente, $validado, verificado: Auth::guard('cliente')->check());
        Adjuntos::guardar($ticket, null, $validado['adjuntos'] ?? []);

        if ($ticket->verificado_en === null) {
            $cliente->notify(new ConfirmarTicket($ticket));

            return redirect("/{$empresa->slug}/pendiente")
                ->with('enlaceDemo', config('attent.demo') ? EnlacesPortal::confirmar($ticket) : null);
        }

        return redirect("/{$empresa->slug}/tickets/{$ticket->numero}");
    }

    public function index(Empresa $empresa): Response
    {
        $tickets = Ticket::query()
            ->delCliente(Auth::guard('cliente')->id())
            ->verificados()
            ->latest()
            ->get()
            ->map(fn (Ticket $t) => [
                'numero' => $t->numero,
                'asunto' => $t->asunto,
                'estado' => $t->etiquetaEstado(),
                'creado' => $t->created_at->toIso8601String(),
                'actualizado' => $t->updated_at->toIso8601String(),
            ]);

        return Inertia::render('portal/mis-tickets', ['tickets' => $tickets]);
    }

    public function show(Empresa $empresa, int $numero): Response
    {
        $ticket = $this->ticketDelCliente($numero);

        return Inertia::render('portal/ticket', [
            'ticket' => VistaTicketPortal::datos($ticket),
        ]);
    }

    public function reabrir(Request $request, Empresa $empresa, int $numero, ReabrirTicket $reabrir): RedirectResponse
    {
        $ticket = self::ticketDelCliente($numero);
        $datos = $request->validate(['motivo' => ['required', 'string', 'max:1000']]);

        $reabrir->ejecutar($ticket, Auth::guard('cliente')->user(), $datos['motivo']);

        return redirect("/{$empresa->slug}/tickets/{$numero}");
    }

    public function valorar(Request $request, Empresa $empresa, int $numero): RedirectResponse
    {
        $ticket = self::ticketDelCliente($numero);
        abort_unless($ticket->cliente_id === Auth::guard('cliente')->id(), 404);

        $datos = $request->validate([
            'valoracion' => ['required', 'integer', 'between:1,5'],
            'comentario' => ['nullable', 'string', 'max:500'],
        ]);
        if (! $ticket->puedeValorarse()) {
            throw \Illuminate\Validation\ValidationException::withMessages(['valoracion' => 'Esta incidencia ya está valorada o todavía no se ha resuelto.']);
        }

        $ticket->update(['valoracion' => $datos['valoracion'], 'valoracion_comentario' => $datos['comentario'] ?? null]);

        return redirect("/{$empresa->slug}/tickets/{$numero}")->with('status', '¡Gracias por tu valoración!');
    }

    public static function ticketDelCliente(int $numero): Ticket
    {
        return Ticket::query()
            ->where('numero', $numero)
            ->delCliente(Auth::guard('cliente')->id())
            ->firstOrFail();
    }
}
