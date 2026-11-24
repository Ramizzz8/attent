<?php

namespace App\Http\Middleware;

use App\Models\Cliente;
use App\Models\Empresa;
use App\Tenancy\EmpresaActual;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resuelve la empresa del portal a partir del slug de la URL y la fija como
 * empresa actual ANTES de que se resuelvan el resto de modelos de la ruta.
 */
class ResolverEmpresaPortal
{
    public function __construct(private EmpresaActual $empresaActual) {}

    public function handle(Request $request, Closure $next): Response
    {
        $parametro = $request->route('empresa');
        $empresa = $parametro instanceof Empresa
            ? $parametro
            : Empresa::where('slug', $parametro)->firstOrFail();

        $request->route()->setParameter('empresa', $empresa);
        $this->empresaActual->set($empresa);

        // Una sesión de cliente de otra empresa no cuenta aquí (sin cerrarla en su empresa).
        $guard = Auth::guard('cliente');
        if ($guard->hasUser() && $guard->user()->empresa_id !== $empresa->id) {
            $guard->forgetUser();
        }

        Inertia::share('portal', fn () => self::datosPortal($empresa));

        return $next($request);
    }

    /** @return array<string, mixed> */
    public static function datosPortal(Empresa $empresa): array
    {
        /** @var Cliente|null $cliente */
        $cliente = Auth::guard('cliente')->user();

        return [
            'empresa' => [
                'nombre' => $empresa->nombre,
                'slug' => $empresa->slug,
                'sector' => $empresa->sector->value,
                'color' => $empresa->color_primario,
                'logo' => $empresa->logo_path ? Storage::disk('public')->url($empresa->logo_path) : null,
                'texto' => $empresa->ajustes['texto_portal'] ?? null,
            ],
            'cliente' => $cliente ? [
                'nombre' => $cliente->nombre,
                'email' => $cliente->email,
                'tienePassword' => $cliente->password !== null,
            ] : null,
        ];
    }
}
