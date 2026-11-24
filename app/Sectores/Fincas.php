<?php

namespace App\Sectores;

use App\Enums\Prioridad;
use App\Enums\SectorClave;
use App\Models\Empresa;
use App\Models\Ticket;

class Fincas extends SectorBase
{
    public function clave(): SectorClave
    {
        return SectorClave::Fincas;
    }

    public function estados(): array
    {
        return [
            'reportada' => 'Reportada',
            'en_revision' => 'En revisión',
            'asignada_proveedor' => 'Asignada a proveedor',
            'visita_programada' => 'Visita programada',
            'resuelta' => 'Resuelta',
            'cerrada' => 'Cerrada',
            'descartada' => 'Descartada',
        ];
    }

    public function estadoInicial(): string
    {
        return 'reportada';
    }

    public function transiciones(): array
    {
        return [
            'reportada' => ['en_revision', 'descartada'],
            'en_revision' => ['asignada_proveedor', 'resuelta', 'descartada'],
            'asignada_proveedor' => ['visita_programada', 'resuelta'],
            'visita_programada' => ['resuelta'],
            'resuelta' => ['cerrada'],
        ];
    }

    public function estadosSoloSistema(): array
    {
        return ['asignada_proveedor'];
    }

    public function validarTransicion(Ticket $ticket, string $destino, ?string $motivo = null): ?string
    {
        if ($destino === 'descartada' && blank($motivo)) {
            return 'Indica el motivo para descartar la incidencia.';
        }

        if ($destino === 'asignada_proveedor' && $ticket->proveedor_id === null) {
            return 'Elige un proveedor antes de asignar.';
        }

        return null;
    }

    public function estadoObjetivoSla(): string
    {
        return 'resuelta';
    }

    public function estadoResuelto(): string
    {
        return 'resuelta';
    }

    public function estadoCerrado(): ?string
    {
        return 'cerrada';
    }

    public function estadosTerminados(): array
    {
        return ['resuelta', 'cerrada', 'descartada'];
    }

    public function estadoReapertura(Ticket $ticket): string
    {
        return $ticket->proveedor_id !== null ? 'asignada_proveedor' : 'en_revision';
    }

    public function prioridadInicial(array $datos, Empresa $empresa): Prioridad
    {
        return ($datos['categoria'] ?? null) === 'seguridad' ? Prioridad::Alta : Prioridad::Media;
    }

    protected function slaPorDefecto(): array
    {
        return ['baja' => 10080, 'media' => 4320, 'alta' => 1440, 'urgente' => 480];
    }

    public function categorias(): array
    {
        return [
            'averia' => 'Avería',
            'limpieza' => 'Limpieza',
            'ruido' => 'Ruido',
            'seguridad' => 'Seguridad',
        ];
    }
}
