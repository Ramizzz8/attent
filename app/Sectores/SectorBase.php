<?php

namespace App\Sectores;

use App\Enums\Prioridad;
use App\Models\Empresa;
use App\Models\Ticket;

abstract class SectorBase implements Sector
{
    /** @return array<string, int> minutos de SLA por valor de prioridad */
    abstract protected function slaPorDefecto(): array;

    public function slaMinutos(Prioridad $prioridad, Empresa $empresa): int
    {
        $personalizado = $empresa->ajustes['sla_minutos'][$prioridad->value] ?? null;

        return (int) ($personalizado ?? $this->slaPorDefecto()[$prioridad->value]);
    }

    public function primeraRespuestaMinutos(Prioridad $prioridad): ?int
    {
        return null;
    }

    public function estadosSoloSistema(): array
    {
        return [];
    }

    public function estadosPausaSla(): array
    {
        return [];
    }

    public function estadosTerminados(): array
    {
        return array_values(array_filter([$this->estadoResuelto(), $this->estadoCerrado()]));
    }

    public function validarTransicion(Ticket $ticket, string $destino, ?string $motivo = null): ?string
    {
        return null;
    }

    public function reglasFormulario(): array
    {
        return [];
    }
}
