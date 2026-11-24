<?php

namespace App\Tickets;

use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\Ticket;
use App\Sectores\SoporteIt;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CrearTicket
{
    /** Columnas del ticket que puede aportar el formulario; lo demás va en 'datos'. */
    private const CAMPOS = [
        'asunto', 'descripcion', 'categoria', 'impacto', 'urgencia',
        'edificio_id', 'zona_id', 'vehiculo_id', 'equipo_id', 'datos',
    ];

    public function __construct(private Sla $sla) {}

    /** @param array<string, mixed> $datos */
    public function ejecutar(Empresa $empresa, Cliente $cliente, array $datos, bool $verificado): Ticket
    {
        if ($cliente->empresa_id !== $empresa->id) {
            throw new InvalidArgumentException('El cliente no pertenece a esta empresa.');
        }

        $sector = $empresa->motor();
        $campos = array_intersect_key($datos, array_flip(self::CAMPOS));

        if ($sector instanceof SoporteIt) {
            $campos['urgencia'] ??= SoporteIt::urgenciaInicial($campos['categoria'] ?? null);
        }

        return DB::transaction(function () use ($empresa, $cliente, $campos, $datos, $sector, $verificado) {
            // Bloquea la fila de la empresa para que dos altas simultáneas no repitan número.
            Empresa::whereKey($empresa->id)->lockForUpdate()->first();
            $numero = (int) Ticket::withoutGlobalScopes()->where('empresa_id', $empresa->id)->max('numero') + 1;

            $ticket = new Ticket([
                ...$campos,
                'empresa_id' => $empresa->id,
                'numero' => $numero,
                'cliente_id' => $cliente->id,
                'estado' => $sector->estadoInicial(),
                'prioridad' => $sector->prioridadInicial($datos + $campos, $empresa),
            ]);
            $ticket->setRelation('empresa', $empresa);

            $sector->antesDeGuardar($ticket, $cliente, $datos);

            if ($verificado) {
                $ticket->verificado_en = now();
                $this->sla->iniciar($ticket);
            }

            $ticket->save();
            $ticket->eventos()->create(['tipo' => 'creado', 'cliente_id' => $cliente->id, 'a' => $ticket->estado]);

            return $ticket;
        });
    }

    /** Marca como verificado un ticket creado desde el portal sin sesión y arranca su SLA. */
    public function verificar(Ticket $ticket): void
    {
        if ($ticket->verificado_en !== null) {
            return;
        }

        $ticket->verificado_en = now();
        $this->sla->iniciar($ticket);
        $ticket->save();
        $ticket->eventos()->create(['tipo' => 'verificado', 'cliente_id' => $ticket->cliente_id]);
    }
}
