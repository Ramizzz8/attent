<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Exige un cliente con sesión en la empresa del portal. Como Cliente está
 * filtrado por empresa, una sesión de otra empresa cuenta como invitado.
 */
class ClientePortal
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::guard('cliente')->check()) {
            return redirect('/'.$request->route('empresa')->slug.'/acceder');
        }

        return $next($request);
    }
}
