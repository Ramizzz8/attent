<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Empresa;
use App\Notifications\EnlaceAcceso;
use App\Portal\EnlacesPortal;
use App\Tickets\CrearTicket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AccesoController extends Controller
{
    private const MENSAJE_ENVIADO = 'Si ese email tiene incidencias con nosotros, te hemos enviado un enlace de acceso.';

    public function create(): Response
    {
        return Inertia::render('portal/acceder', ['status' => session('status'), 'enlaceDemo' => session('enlaceDemo')]);
    }

    /** Envía el enlace mágico. Responde igual exista o no el email, para no revelar quién es cliente. */
    public function store(Request $request, Empresa $empresa): RedirectResponse
    {
        $email = mb_strtolower($request->validate(['email' => ['required', 'email']])['email']);

        $cliente = Cliente::where('email', $email)->first();
        $cliente?->notify(new EnlaceAcceso);

        return redirect("/{$empresa->slug}/acceder")
            ->with('status', self::MENSAJE_ENVIADO)
            ->with('enlaceDemo', config('attent.demo') && $cliente ? EnlacesPortal::entrar($cliente) : null);
    }

    public function entrar(Request $request, Empresa $empresa, int $cliente): RedirectResponse
    {
        $this->iniciarSesion($request, Cliente::findOrFail($cliente));

        return redirect("/{$empresa->slug}/mis-tickets");
    }

    public function confirmar(Request $request, Empresa $empresa, int $ticket, CrearTicket $crear): RedirectResponse
    {
        $t = \App\Models\Ticket::findOrFail($ticket);
        $crear->verificar($t);
        $this->iniciarSesion($request, $t->cliente);

        return redirect("/{$empresa->slug}/tickets/{$t->numero}");
    }

    public function enlace(Request $request, Empresa $empresa, int $cliente, int $ticket): RedirectResponse
    {
        $t = \App\Models\Ticket::findOrFail($ticket);
        $this->iniciarSesion($request, Cliente::findOrFail($cliente));

        return redirect("/{$empresa->slug}/tickets/{$t->numero}");
    }

    public function login(Request $request, Empresa $empresa): RedirectResponse
    {
        $datos = $request->validate(['email' => ['required', 'email'], 'password' => ['required', 'string']]);

        if (! Auth::guard('cliente')->attempt(['email' => mb_strtolower($datos['email']), 'password' => $datos['password']], true)) {
            throw ValidationException::withMessages(['email' => 'El email o la contraseña no son correctos.']);
        }

        $request->session()->regenerate();

        return redirect("/{$empresa->slug}/mis-tickets");
    }

    public function salir(Request $request, Empresa $empresa): RedirectResponse
    {
        Auth::guard('cliente')->logout();
        $request->session()->regenerateToken();

        return redirect("/{$empresa->slug}");
    }

    public function password(Request $request, Empresa $empresa): RedirectResponse
    {
        $datos = $request->validate(['password' => ['required', 'string', 'min:8', 'confirmed']]);

        Auth::guard('cliente')->user()->update(['password' => $datos['password']]);

        return back()->with('status', 'Contraseña guardada. Ya puedes entrar con ella.');
    }

    public function cuenta(): Response
    {
        return Inertia::render('portal/cuenta', ['status' => session('status')]);
    }

    private function iniciarSesion(Request $request, Cliente $cliente): void
    {
        $cliente->email_verificado_en ??= now();
        $cliente->save();

        Auth::guard('cliente')->login($cliente, remember: true);
        $request->session()->regenerate();
    }
}
