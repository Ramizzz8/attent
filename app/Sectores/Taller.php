<?php

namespace App\Sectores;

use App\Enums\Prioridad;
use App\Enums\SectorClave;
use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\Ticket;
use App\Models\Vehiculo;

class Taller extends SectorBase
{
    public function clave(): SectorClave
    {
        return SectorClave::Taller;
    }

    public function estados(): array
    {
        return [
            'recibido' => 'Recibido',
            'en_diagnostico' => 'En diagnóstico',
            'presupuesto_enviado' => 'Presupuesto enviado',
            'aprobado' => 'Presupuesto aprobado',
            'rechazado' => 'Presupuesto rechazado',
            'en_reparacion' => 'En reparación',
            'esperando_piezas' => 'Esperando piezas',
            'listo_recoger' => 'Listo para recoger',
            'entregado' => 'Entregado',
        ];
    }

    public function estadoInicial(): string
    {
        return 'recibido';
    }

    public function descripciones(): array
    {
        return [
            'recibido' => 'El vehículo ha llegado y espera a que alguien lo revise.',
            'en_diagnostico' => 'Un mecánico está buscando la avería para preparar el presupuesto.',
            'presupuesto_enviado' => 'El cliente tiene el presupuesto y debe aprobarlo o rechazarlo. El plazo está en pausa.',
            'aprobado' => 'El cliente ha dado el sí: ya se puede empezar a reparar.',
            'rechazado' => 'El cliente no quiere la reparación. Envía otro presupuesto o deja el coche listo para recoger.',
            'en_reparacion' => 'El vehículo se está reparando.',
            'esperando_piezas' => 'La reparación espera a que lleguen piezas. El plazo está en pausa.',
            'listo_recoger' => 'El trabajo está hecho y el cliente puede pasar a por su vehículo.',
            'entregado' => 'El cliente ha recogido el vehículo. Puede reabrir la incidencia durante 7 días.',
        ];
    }

    public function siguientePaso(): array
    {
        return [
            'recibido' => 'en_diagnostico',
            'aprobado' => 'en_reparacion',
            'en_reparacion' => 'listo_recoger',
            'esperando_piezas' => 'en_reparacion',
            'rechazado' => 'listo_recoger',
            'listo_recoger' => 'entregado',
        ];
    }

    public function transiciones(): array
    {
        return [
            'recibido' => ['en_diagnostico'],
            'en_diagnostico' => ['presupuesto_enviado'],
            'aprobado' => ['en_reparacion'],
            'en_reparacion' => ['esperando_piezas', 'listo_recoger'],
            'esperando_piezas' => ['en_reparacion'],
            'rechazado' => ['presupuesto_enviado', 'listo_recoger'],
            'listo_recoger' => ['entregado'],
        ];
    }

    public function estadosSoloSistema(): array
    {
        return ['presupuesto_enviado', 'aprobado', 'rechazado'];
    }

    public function validarTransicion(Ticket $ticket, string $destino, ?string $motivo = null): ?string
    {
        if ($destino === 'en_reparacion' && $ticket->presupuestoVigente()?->estado !== 'aprobado') {
            return 'No se puede reparar sin un presupuesto aprobado.';
        }

        return null;
    }

    public function estadosPausaSla(): array
    {
        return ['presupuesto_enviado', 'esperando_piezas'];
    }

    public function estadoObjetivoSla(): string
    {
        return 'presupuesto_enviado';
    }

    public function estadoResuelto(): string
    {
        return 'entregado';
    }

    public function estadoCerrado(): ?string
    {
        return null;
    }

    public function estadoReapertura(Ticket $ticket): string
    {
        return $ticket->presupuestoVigente()?->estado === 'aprobado' ? 'en_reparacion' : 'en_diagnostico';
    }

    public function prioridadInicial(array $datos, Empresa $empresa): Prioridad
    {
        return Prioridad::Media;
    }

    protected function slaPorDefecto(): array
    {
        // SLA de diagnóstico: 48 h desde que se recibe el vehículo.
        return ['baja' => 2880, 'media' => 2880, 'alta' => 2880, 'urgente' => 2880];
    }

    /** Busca el vehículo por matrícula normalizada o lo crea, y lo enlaza al ticket. */
    public function antesDeGuardar(Ticket $ticket, Cliente $cliente, array $datos): void
    {
        $d = $datos['datos'] ?? [];
        if (blank($d['matricula'] ?? null)) {
            return;
        }

        $vehiculo = Vehiculo::firstOrNew([
            'empresa_id' => $ticket->empresa_id,
            'matricula' => Vehiculo::normalizarMatricula($d['matricula']),
        ]);
        // Si el coche cambió de dueño, pasa a ser del cliente actual.
        $vehiculo->cliente_id = $cliente->id;
        $vehiculo->fill(array_filter([
            'marca' => $d['marca'] ?? null,
            'modelo' => $d['modelo'] ?? null,
            'km' => isset($d['km']) && $d['km'] !== '' ? (int) $d['km'] : null,
        ], fn ($v) => $v !== null && $v !== ''));
        $vehiculo->save();

        $ticket->vehiculo_id = $vehiculo->id;
    }

    public function reglasFormulario(): array
    {
        return [
            'datos' => ['required', 'array'],
            'datos.matricula' => ['required', 'string', 'max:12', 'regex:/^[A-Za-z0-9 \-]+$/'],
            'datos.marca' => ['nullable', 'string', 'max:50'],
            'datos.modelo' => ['nullable', 'string', 'max:50'],
            'datos.km' => ['nullable', 'integer', 'min:0', 'max:2000000'],
        ];
    }

    public function categorias(): array
    {
        return [
            'mecanica' => 'Mecánica',
            'electricidad' => 'Electricidad',
            'carroceria' => 'Carrocería',
            'neumaticos' => 'Neumáticos',
            'revision' => 'Revisión',
            'otro' => 'Otro',
        ];
    }
}
