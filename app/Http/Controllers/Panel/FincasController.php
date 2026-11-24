<?php

namespace App\Http\Controllers\Panel;

use App\Fincas\AsignarProveedor;
use App\Http\Controllers\Controller;
use App\Models\Edificio;
use App\Models\Proveedor;
use App\Models\Ticket;
use App\Models\Zona;
use App\Tenancy\EmpresaActual;
use App\Tickets\TransicionNoPermitida;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/** Panel del administrador de fincas: edificios, zonas, proveedores y asignación. */
class FincasController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('panel/fincas/edificios', [
            'edificios' => Edificio::with(['zonas', 'proveedores'])->orderBy('nombre')->get()->map(fn (Edificio $e) => [
                'id' => $e->id,
                'nombre' => $e->nombre,
                'direccion' => $e->direccion,
                'enlace' => url('/'.app(EmpresaActual::class)->get()->slug.'/edificio/'.$e->slug),
                'zonas' => $e->zonas->map(fn (Zona $z) => ['id' => $z->id, 'nombre' => $z->nombre, 'prioridadAlta' => $z->prioridad_alta]),
                'proveedores' => $e->proveedores->pluck('nombre'),
            ]),
            'proveedores' => Proveedor::with('edificios')->orderBy('nombre')->get()->map(fn (Proveedor $p) => [
                'id' => $p->id,
                'nombre' => $p->nombre,
                'oficio' => $p->oficio,
                'email' => $p->email,
                'telefono' => $p->telefono,
                'edificios' => $p->edificios->pluck('id'),
            ]),
        ]);
    }

    /** Crea un edificio. Las zonas van una por línea; un "*" al final la marca como prioritaria. */
    public function storeEdificio(Request $request, EmpresaActual $empresaActual): RedirectResponse
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'direccion' => ['required', 'string', 'max:200'],
            'zonas' => ['nullable', 'string', 'max:2000'],
        ]);

        $edificio = Edificio::create([
            'nombre' => $datos['nombre'],
            'direccion' => $datos['direccion'],
            'slug' => Edificio::slugLibre($empresaActual->id(), $datos['nombre']),
        ]);

        collect(preg_split('/\r?\n/', $datos['zonas'] ?? ''))
            ->map(fn ($l) => trim($l))
            ->filter()
            ->each(fn ($l) => $edificio->zonas()->create([
                'nombre' => rtrim($l, '* '),
                'prioridad_alta' => str_ends_with($l, '*'),
            ]));

        return back()->with('status', 'Edificio creado.');
    }

    public function storeProveedor(Request $request, EmpresaActual $empresaActual): RedirectResponse
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'oficio' => ['required', 'string', 'max:60'],
            'email' => ['required', 'email', 'max:150'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'edificios' => ['array'],
            'edificios.*' => ['integer', Rule::exists('edificios', 'id')->where('empresa_id', $empresaActual->id())],
        ]);

        $proveedor = Proveedor::create(collect($datos)->except('edificios')->all());
        $proveedor->edificios()->sync($datos['edificios'] ?? []);

        return back()->with('status', 'Proveedor creado.');
    }

    public function asignar(Request $request, int $numero, AsignarProveedor $asignar): RedirectResponse
    {
        $ticket = Ticket::where('numero', $numero)->firstOrFail();
        $datos = $request->validate(['proveedor_id' => ['required', 'integer']]);
        $proveedor = Proveedor::find($datos['proveedor_id'])
            ?? throw ValidationException::withMessages(['proveedor_id' => 'Ese proveedor no existe.']);

        try {
            $asignar->ejecutar($ticket, $proveedor, $request->user());
        } catch (TransicionNoPermitida $e) {
            throw ValidationException::withMessages(['proveedor_id' => $e->getMessage()]);
        }

        return back()->with('exito', "Proveedor asignado: {$proveedor->nombre}. Le hemos enviado el enlace.");
    }
}
