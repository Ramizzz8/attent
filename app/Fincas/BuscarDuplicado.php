<?php

namespace App\Fincas;

use App\Models\Ticket;
use App\Sectores\Fincas;

/** Busca una incidencia abierta igual (mismo edificio, zona y categoría) para no duplicarla. */
class BuscarDuplicado
{
    public function ejecutar(int $edificioId, int $zonaId, string $categoria): ?Ticket
    {
        return Ticket::query()
            ->verificados()
            ->where('edificio_id', $edificioId)
            ->where('zona_id', $zonaId)
            ->where('categoria', $categoria)
            ->abiertos(new Fincas)
            ->oldest()
            ->first();
    }
}
