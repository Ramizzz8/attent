<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * En la demo pública, las cuentas de ejemplo las comparten todos los visitantes:
 * no se puede cambiar su acceso (email, contraseña, 2FA, llaves) ni borrarlas.
 */
class ProtegerDemo
{
    public const AVISO = 'Desactivado en la demo: esta cuenta la comparten todos los visitantes.';

    private const RUTAS_DE_LA_PROPIA_CUENTA = [
        'profile.update', 'profile.destroy', 'user-password.update',
        'two-factor.enable', 'two-factor.confirm', 'two-factor.disable', 'two-factor.regenerate-recovery-codes',
        'passkey.store', 'passkey.destroy',
    ];

    private const RUTAS_SOBRE_EMPLEADOS = ['panel.empleados.update', 'panel.empleados.destroy'];

    public function handle(Request $request, Closure $next): Response
    {
        if (! config('attent.demo') || $request->isMethodSafe()) {
            return $next($request);
        }

        $ruta = $request->route()?->getName();
        $bloqueado = match (true) {
            in_array($ruta, self::RUTAS_DE_LA_PROPIA_CUENTA, true) => (bool) $request->user()?->esCuentaDemo(),
            in_array($ruta, self::RUTAS_SOBRE_EMPLEADOS, true) => (bool) User::find($request->route('empleado'))?->esCuentaDemo(),
            default => false,
        };

        if (! $bloqueado) {
            return $next($request);
        }

        return $request->expectsJson()
            ? response()->json(['message' => self::AVISO], 403)
            : back()->withErrors(['demo' => self::AVISO]);
    }
}
