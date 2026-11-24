<?php

namespace App\Sectores;

use App\Enums\Prioridad;
use App\Enums\SectorClave;
use App\Models\Empresa;
use App\Models\Ticket;

/**
 * Define el comportamiento de los tickets de un sector: estados,
 * transiciones, prioridad, SLA y formulario del portal.
 */
interface Sector
{
    public function clave(): SectorClave;

    /** @return array<string, string> clave del estado => etiqueta */
    public function estados(): array;

    public function estadoInicial(): string;

    /** @return array<string, list<string>> avances permitidos al empleado */
    public function transiciones(): array;

    /** @return list<string> estados a los que solo se llega mediante una acción del módulo */
    public function estadosSoloSistema(): array;

    /** Devuelve el motivo por el que no se permite la transición, o null si se permite. */
    public function validarTransicion(Ticket $ticket, string $destino, ?string $motivo = null): ?string;

    /** @return list<string> */
    public function estadosPausaSla(): array;

    public function estadoObjetivoSla(): string;

    public function estadoResuelto(): string;

    public function estadoCerrado(): ?string;

    /** @return list<string> estados en los que el ticket ya no está abierto */
    public function estadosTerminados(): array;

    public function estadoReapertura(Ticket $ticket): string;

    /** @param array<string, mixed> $datos */
    public function prioridadInicial(array $datos, Empresa $empresa): Prioridad;

    public function slaMinutos(Prioridad $prioridad, Empresa $empresa): int;

    public function primeraRespuestaMinutos(Prioridad $prioridad): ?int;

    /** @return array<string, string> */
    public function categorias(): array;

    /** @return array<string, mixed> reglas de validación propias del formulario del portal */
    public function reglasFormulario(): array;
}
