<?php

namespace App\Sectores;

use App\Enums\Prioridad;
use App\Enums\SectorClave;
use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\Equipo;
use App\Models\Ticket;
use App\SoporteIt\MatrizPrioridad;

class SoporteIt extends SectorBase
{
    public function clave(): SectorClave
    {
        return SectorClave::SoporteIt;
    }

    public function estados(): array
    {
        return [
            'nuevo' => 'Nuevo',
            'asignado' => 'Asignado',
            'en_curso' => 'En curso',
            'pendiente_usuario' => 'Pendiente del usuario',
            'resuelto' => 'Resuelto',
            'cerrado' => 'Cerrado',
        ];
    }

    public function estadoInicial(): string
    {
        return 'nuevo';
    }

    public function transiciones(): array
    {
        return [
            'nuevo' => ['asignado'],
            'asignado' => ['en_curso'],
            'en_curso' => ['pendiente_usuario', 'resuelto'],
            'pendiente_usuario' => ['en_curso'],
            'resuelto' => ['cerrado'],
        ];
    }

    public function validarTransicion(Ticket $ticket, string $destino, ?string $motivo = null): ?string
    {
        if ($destino === 'asignado' && $ticket->asignado_a === null) {
            return 'Asigna el ticket a un empleado primero.';
        }

        return null;
    }

    public function estadosPausaSla(): array
    {
        return ['pendiente_usuario'];
    }

    public function estadoObjetivoSla(): string
    {
        return 'resuelto';
    }

    public function estadoResuelto(): string
    {
        return 'resuelto';
    }

    public function estadoCerrado(): ?string
    {
        return 'cerrado';
    }

    public function estadoReapertura(Ticket $ticket): string
    {
        return 'en_curso';
    }

    public function prioridadInicial(array $datos, Empresa $empresa): Prioridad
    {
        return MatrizPrioridad::calcular(
            $datos['impacto'] ?? 'individual',
            $datos['urgencia'] ?? self::urgenciaInicial($datos['categoria'] ?? null),
        );
    }

    public static function urgenciaInicial(?string $categoria): string
    {
        return $categoria === 'red' ? 'alta' : 'media';
    }

    protected function slaPorDefecto(): array
    {
        return ['baja' => 4320, 'media' => 1440, 'alta' => 480, 'urgente' => 240];
    }

    public function primeraRespuestaMinutos(Prioridad $prioridad): ?int
    {
        return match ($prioridad) {
            Prioridad::Baja => 480,
            Prioridad::Media => 240,
            Prioridad::Alta => 60,
            Prioridad::Urgente => 30,
        };
    }

    public function reglasFormulario(): array
    {
        return [
            'impacto' => ['required', 'in:'.implode(',', MatrizPrioridad::IMPACTOS)],
            'equipo_codigo' => ['nullable', 'string', 'max:30', function (string $campo, mixed $valor, \Closure $fallo) {
                if (! Equipo::where('codigo', Equipo::normalizarCodigo((string) $valor))->exists()) {
                    $fallo('No hay ningún equipo con ese código. Míralo en la etiqueta del equipo o déjalo vacío.');
                }
            }],
        ];
    }

    /** Enlaza el equipo indicado por su código de inventario. */
    public function antesDeGuardar(Ticket $ticket, Cliente $cliente, array $datos): void
    {
        if (filled($datos['equipo_codigo'] ?? null)) {
            $ticket->equipo_id = Equipo::where('codigo', Equipo::normalizarCodigo($datos['equipo_codigo']))->value('id');
        }
    }

    public function categorias(): array
    {
        return [
            'hardware' => 'Hardware',
            'software' => 'Software',
            'red' => 'Red',
            'accesos' => 'Accesos y cuentas',
        ];
    }
}
