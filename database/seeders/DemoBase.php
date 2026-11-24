<?php

namespace Database\Seeders;

use App\Enums\Rol;
use App\Enums\SectorClave;
use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\Ticket;
use App\Models\User;
use App\Sectores\SoporteIt;
use App\Tenancy\EmpresaActual;
use App\Tickets\CambiarEstado;
use App\Tickets\CrearTicket;
use App\Tickets\EnviarMensaje;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Utilidades para sembrar historias realistas: cada incidencia se crea y avanza con las
 * mismas acciones que usa la aplicación, moviendo el reloj para que fechas, SLA e historial cuadren.
 */
abstract class DemoBase extends Seeder
{
    protected Empresa $empresa;

    /** @var list<User> [admin, empleado1, empleado2] */
    protected array $equipo = [];

    /** @var list<Cliente> */
    protected array $clientes = [];

    protected Carbon $ahora;

    protected function empresa(string $nombre, string $slug, SectorClave $sector, string $color, ?string $texto = null): void
    {
        $this->ahora = Carbon::now();
        $this->empresa = Empresa::create([
            'nombre' => $nombre,
            'slug' => $slug,
            'sector' => $sector,
            'color_primario' => $color,
            'ajustes' => $texto ? ['texto_portal' => $texto] : null,
        ]);
        app(EmpresaActual::class)->set($this->empresa);

        $nombres = [['Administración', Rol::Admin], ['Empleado Uno', Rol::Empleado], ['Empleado Dos', Rol::Empleado]];
        $this->equipo = [];
        foreach ($nombres as $i => [$n, $rol]) {
            $email = $i === 0 ? "admin@{$slug}.test" : "empleado{$i}@{$slug}.test";
            $u = new User(['name' => $n, 'email' => $email, 'password' => 'password', 'empresa_id' => $this->empresa->id, 'rol' => $rol]);
            $u->email_verified_at = now();
            $u->save();
            $this->equipo[] = $u;
        }
    }

    /** @param list<array{0: string, 1: string, 2?: string}> $clientes nombre, email, teléfono */
    protected function clientes(array $clientes): void
    {
        $this->clientes = array_map(fn ($c) => Cliente::create([
            'nombre' => $c[0],
            'email' => $c[1],
            'telefono' => $c[2] ?? null,
            'email_verificado_en' => now(),
        ]), $clientes);
    }

    protected function renombrarEquipo(string $admin, string $uno, string $dos): void
    {
        foreach ([$admin, $uno, $dos] as $i => $n) {
            $this->equipo[$i]->update(['name' => $n]);
        }
    }

    /** Crea una incidencia "hace X horas" desde el portal. */
    protected function crear(int $cliente, float $horasAtras, array $datos): Ticket
    {
        $this->reloj($this->ahora->copy()->subMinutes((int) round($horasAtras * 60)));

        return app(CrearTicket::class)->ejecutar($this->empresa, $this->clientes[$cliente], $datos, verificado: true);
    }

    /** Avanza el reloj X horas desde el último paso. */
    protected function luego(float $horas): void
    {
        $this->reloj(Carbon::now()->addMinutes((int) round($horas * 60)));
    }

    protected function estado(Ticket $t, string $destino, ?int $empleado = 1, float $horas = 1, ?string $motivo = null, bool $sistema = false): Ticket
    {
        $this->luego($horas);

        return app(CambiarEstado::class)->ejecutar($t->fresh(), $destino, $empleado === null ? null : $this->equipo[$empleado], $motivo, $sistema);
    }

    protected function asignar(Ticket $t, int $empleado, float $horas = 0.5): void
    {
        $this->luego($horas);
        $t->update(['asignado_a' => $this->equipo[$empleado]->id]);
        $t->eventos()->create(['tipo' => 'asignado', 'user_id' => $this->equipo[0]->id, 'datos' => ['de' => null, 'a' => $this->equipo[$empleado]->name]]);

        if ($t->estado === 'nuevo' && $t->sector() instanceof SoporteIt) {
            app(CambiarEstado::class)->ejecutar($t->fresh(), 'asignado', $this->equipo[0]);
        }
    }

    protected function mensaje(Ticket $t, Model $autor, string $texto, bool $interno = false, float $horas = 1): void
    {
        $this->luego($horas);
        app(EnviarMensaje::class)->ejecutar($t->fresh(), $autor, $texto, interno: $interno);
    }

    protected function valorar(Ticket $t, int $nota, ?string $comentario = null): void
    {
        $t->update(['valoracion' => $nota, 'valoracion_comentario' => $comentario]);
    }

    protected function reloj(Carbon $momento): void
    {
        Carbon::setTestNow($momento);
    }

    protected function terminar(): void
    {
        Carbon::setTestNow();
        app(EmpresaActual::class)->set(null);
    }
}
