<?php

namespace App\Console\Commands;

use App\Models\Empresa;
use App\Models\Ticket;
use App\Tickets\CambiarEstado;
use App\Tickets\ReabrirTicket;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('attent:cerrar-resueltos')]
#[Description('Cierra las incidencias resueltas hace más de 7 días que el cliente no ha reabierto')]
class CerrarResueltos extends Command
{
    public function handle(CambiarEstado $cambiarEstado): int
    {
        $cerrados = 0;

        Empresa::query()->each(function (Empresa $empresa) use ($cambiarEstado, &$cerrados) {
            $sector = $empresa->motor();
            if ($sector->estadoCerrado() === null) {
                return;
            }

            Ticket::withoutGlobalScopes()
                ->where('empresa_id', $empresa->id)
                ->where('estado', $sector->estadoResuelto())
                ->where('resuelto_en', '<', now()->subDays(ReabrirTicket::DIAS))
                ->each(function (Ticket $t) use ($cambiarEstado, $sector, &$cerrados) {
                    $cambiarEstado->ejecutar($t, $sector->estadoCerrado(), null, sistema: true);
                    $cerrados++;
                });
        });

        $this->info("Incidencias cerradas: {$cerrados}");

        return self::SUCCESS;
    }
}
