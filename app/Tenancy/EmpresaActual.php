<?php

namespace App\Tenancy;

use App\Models\Empresa;

/**
 * Empresa sobre la que trabaja la petición actual (panel o portal).
 * Registrada como "scoped": los modelos con PerteneceAEmpresa filtran por ella.
 */
class EmpresaActual
{
    private ?Empresa $empresa = null;

    public function set(?Empresa $empresa): void
    {
        $this->empresa = $empresa;
    }

    public function get(): ?Empresa
    {
        return $this->empresa;
    }

    public function id(): ?int
    {
        return $this->empresa?->id;
    }
}
