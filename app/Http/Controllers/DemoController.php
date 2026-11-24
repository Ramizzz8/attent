<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** Demo pública: entrar al panel de una empresa de ejemplo con un clic. */
class DemoController extends Controller
{
    public function entrar(Request $request, string $empresa): RedirectResponse
    {
        abort_unless(config('attent.demo'), 404);

        $e = Empresa::where('slug', $empresa)->firstOrFail();
        // Solo la cuenta de demostración de esa empresa; nunca un usuario real.
        $admin = User::where('empresa_id', $e->id)->where('email', "admin@{$e->slug}.test")->firstOrFail();

        Auth::guard('web')->login($admin);
        $request->session()->regenerate();

        return redirect('/panel');
    }

    /** @return list<array{sector: string, etiqueta: string, slug: string}> */
    public static function accesos(): array
    {
        if (! config('attent.demo')) {
            return [];
        }

        $etiquetas = ['taller' => 'Entrar como taller', 'fincas' => 'Entrar como fincas', 'soporte_it' => 'Entrar como soporte'];

        return Empresa::orderBy('id')->get()
            ->filter(fn (Empresa $e) => User::where('empresa_id', $e->id)->where('email', "admin@{$e->slug}.test")->exists())
            ->map(fn (Empresa $e) => ['sector' => $e->sector->value, 'etiqueta' => $etiquetas[$e->sector->value], 'slug' => $e->slug])
            ->values()
            ->all();
    }
}
