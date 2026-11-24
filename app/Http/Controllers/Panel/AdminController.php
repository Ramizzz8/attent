<?php

namespace App\Http\Controllers\Panel;

use App\Enums\Prioridad;
use App\Enums\Rol;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Tenancy\EmpresaActual;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/** Administración de la empresa: empleados y ajustes del portal. */
class AdminController extends Controller
{
    public function __construct(private EmpresaActual $empresaActual) {}

    public function empleados(Request $request): Response
    {
        return Inertia::render('panel/admin/empleados', [
            'empleados' => User::where('empresa_id', $this->empresaActual->id())->orderBy('name')->get()->map(fn (User $u) => [
                'id' => $u->id,
                'nombre' => $u->name,
                'email' => $u->email,
                'rol' => $u->rol->value,
                'soyYo' => $u->id === $request->user()->id,
            ]),
            'status' => session('status'),
        ]);
    }

    public function crearEmpleado(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'rol' => ['required', Rule::enum(Rol::class)],
        ]);

        $user = new User([...$datos, 'empresa_id' => $this->empresaActual->id(), 'password' => Str::random(40)]);
        // El administrador responde por el email; el empleado lo confirma al crear su contraseña.
        $user->email_verified_at = now();
        $user->save();

        Password::sendResetLink(['email' => $user->email]);

        return back()->with('status', "Hemos enviado a {$user->email} un enlace para crear su contraseña.");
    }

    public function cambiarRol(Request $request, int $empleado): RedirectResponse
    {
        $user = $this->empleadoDeMiEmpresa($empleado);
        $rol = Rol::from($request->validate(['rol' => ['required', Rule::enum(Rol::class)]])['rol']);

        if ($rol === Rol::Empleado && $this->esUltimoAdmin($user)) {
            throw ValidationException::withMessages(['rol' => 'La empresa necesita al menos un administrador.']);
        }

        $user->update(['rol' => $rol]);

        return back();
    }

    public function eliminarEmpleado(Request $request, int $empleado): RedirectResponse
    {
        $user = $this->empleadoDeMiEmpresa($empleado);

        if ($user->is($request->user()) || $this->esUltimoAdmin($user)) {
            throw ValidationException::withMessages(['empleado' => 'No puedes darte de baja a ti mismo ni dejar la empresa sin administrador.']);
        }

        $user->delete();

        return back()->with('status', 'Empleado dado de baja. Sus tickets quedan sin asignar.');
    }

    public function ajustes(): Response
    {
        $empresa = $this->empresaActual->get();
        $sector = $empresa->motor();

        return Inertia::render('panel/admin/ajustes', [
            'empresa' => [
                'nombre' => $empresa->nombre,
                'slug' => $empresa->slug,
                'sector' => $empresa->sector->etiqueta(),
                'color_primario' => $empresa->color_primario,
                'texto_portal' => $empresa->ajustes['texto_portal'] ?? '',
                'logo' => $empresa->logo_path ? Storage::disk('public')->url($empresa->logo_path) : null,
                'urlPortal' => url('/'.$empresa->slug),
            ],
            'sla' => collect(Prioridad::cases())->map(fn (Prioridad $p) => [
                'prioridad' => $p->value,
                'etiqueta' => $p->etiqueta(),
                'minutos' => $sector->slaMinutos($p, $empresa),
            ]),
            'status' => session('status'),
        ]);
    }

    public function guardarAjustes(Request $request): RedirectResponse
    {
        $empresa = $this->empresaActual->get();
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'color_primario' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'texto_portal' => ['nullable', 'string', 'max:300'],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:1024'],
            'sla_minutos' => ['nullable', 'array'],
            'sla_minutos.*' => ['nullable', 'integer', 'min:30', 'max:43200'],
        ], ['color_primario.regex' => 'El color debe tener el formato #RRGGBB.']);

        if ($request->hasFile('logo')) {
            if ($empresa->logo_path) {
                Storage::disk('public')->delete($empresa->logo_path);
            }
            $empresa->logo_path = $request->file('logo')->store("logos/{$empresa->id}", 'public');
        }

        $sla = collect($datos['sla_minutos'] ?? [])
            ->only(array_map(fn (Prioridad $p) => $p->value, Prioridad::cases()))
            ->filter(fn ($v) => $v !== null)
            ->map(fn ($v) => (int) $v)
            ->all();

        $empresa->fill([
            'nombre' => $datos['nombre'],
            'color_primario' => strtolower($datos['color_primario']),
            'ajustes' => array_filter([
                ...($empresa->ajustes ?? []),
                'texto_portal' => $datos['texto_portal'] ?? null,
                'sla_minutos' => $sla ?: ($empresa->ajustes['sla_minutos'] ?? null),
            ], fn ($v) => $v !== null && $v !== ''),
        ])->save();

        return back()->with('status', 'Ajustes guardados.');
    }

    private function empleadoDeMiEmpresa(int $id): User
    {
        return User::where('empresa_id', $this->empresaActual->id())->findOrFail($id);
    }

    private function esUltimoAdmin(User $user): bool
    {
        return $user->esAdmin()
            && User::where('empresa_id', $user->empresa_id)->where('rol', Rol::Admin)->count() === 1;
    }
}
