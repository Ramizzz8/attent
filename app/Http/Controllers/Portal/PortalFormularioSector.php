<?php

namespace App\Http\Controllers\Portal;

use App\Models\Empresa;
use Illuminate\Http\Request;

/** Datos extra que necesita el formulario del portal según el sector. */
final class PortalFormularioSector
{
    /** @return array<string, mixed> */
    public static function datos(Empresa $empresa, Request $request): array
    {
        return [];
    }
}
