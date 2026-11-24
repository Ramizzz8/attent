<?php

namespace App\Http\Middleware;

use App\Tenancy\EmpresaActual;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolverEmpresaPanel
{
    public function __construct(private EmpresaActual $empresaActual) {}

    public function handle(Request $request, Closure $next): Response
    {
        $empresa = $request->user()?->empresa;
        abort_if($empresa === null, 403, 'Tu usuario no pertenece a ninguna empresa.');

        $this->empresaActual->set($empresa);

        return $next($request);
    }
}
