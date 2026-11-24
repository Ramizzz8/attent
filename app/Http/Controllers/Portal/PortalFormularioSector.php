<?php

namespace App\Http\Controllers\Portal;

use App\Enums\SectorClave;
use App\Models\Edificio;
use App\Models\Empresa;
use App\Models\Zona;
use Illuminate\Http\Request;

/** Datos extra que necesita el formulario del portal según el sector. */
final class PortalFormularioSector
{
    /** @return array<string, mixed> */
    public static function datos(Empresa $empresa, Request $request): array
    {
        return match ($empresa->sector) {
            SectorClave::Fincas => [
                'edificios' => Edificio::with('zonas')->orderBy('nombre')->get()->map(fn (Edificio $e) => [
                    'id' => $e->id,
                    'nombre' => $e->nombre,
                    'direccion' => $e->direccion,
                    'zonas' => $e->zonas->map(fn (Zona $z) => ['id' => $z->id, 'nombre' => $z->nombre]),
                ]),
            ],
            default => [],
        };
    }
}
