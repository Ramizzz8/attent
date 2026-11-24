<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\Ticket;
use App\Notifications\ConfirmarTicket;
use App\Tickets\Adjuntos;
use App\Tickets\CrearTicket;
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
        return Inertia::render('portal/nueva', [
            'categorias' => collect($empresa->motor()->categorias())
                ->map(fn ($etiqueta, $valor) => ['valor' => $valor, 'etiqueta' => $etiqueta])->values(),
            ...PortalFormularioSector::datos($empresa, $request),
        ]);
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

            return redirect("/{$empresa->slug}/pendiente");
        }

        return redirect("/{$empresa->slug}/tickets/{$ticket->numero}");
    }

    public function index(Empresa $empresa): Response
    {
        $tickets = Ticket::query()
            ->where('cliente_id', Auth::guard('cliente')->id())
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

    public static function ticketDelCliente(int $numero): Ticket
    {
        return Ticket::query()
            ->where('numero', $numero)
            ->where('cliente_id', Auth::guard('cliente')->id())
            ->firstOrFail();
    }
}
