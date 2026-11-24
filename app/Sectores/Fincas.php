<?php

namespace App\Sectores;

use App\Enums\Prioridad;
use App\Enums\SectorClave;
use App\Models\Empresa;
use App\Models\Proveedor;
use App\Models\Ticket;
use App\Models\Zona;
use Illuminate\Validation\Rule;

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

        if ($destino === 'asignada_proveedor') {
            if ($ticket->proveedor_id === null) {
                return 'Elige un proveedor antes de asignar.';
            }
            if (! Proveedor::find($ticket->proveedor_id)?->atiende((int) $ticket->edificio_id)) {
                return 'Ese proveedor no atiende este edificio.';
            }
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
        $zonaPrioritaria = isset($datos['zona_id']) && Zona::whereKey($datos['zona_id'])->value('prioridad_alta');

        return ($datos['categoria'] ?? null) === 'seguridad' || $zonaPrioritaria ? Prioridad::Alta : Prioridad::Media;
    }

    protected function slaPorDefecto(): array
    {
        return ['baja' => 10080, 'media' => 4320, 'alta' => 1440, 'urgente' => 480];
    }

    public function reglasFormulario(): array
    {
        return [
            'edificio_id' => ['required', 'integer', Rule::exists('edificios', 'id')->where('empresa_id', app(\App\Tenancy\EmpresaActual::class)->id())],
            'zona_id' => ['required', 'integer', Rule::exists('zonas', 'id')->where('edificio_id', (int) request('edificio_id'))],
            'datos' => ['required', 'array'],
            'datos.vivienda' => ['required', 'string', 'max:30'],
        ];
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
