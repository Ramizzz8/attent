<?php

namespace App\Sectores;

use App\Enums\Prioridad;
use App\Enums\SectorClave;
use App\Models\Empresa;
use App\Models\Ticket;

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
