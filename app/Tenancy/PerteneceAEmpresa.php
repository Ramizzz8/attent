<?php

namespace App\Tenancy;

use App\Models\Empresa;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Aísla el modelo por empresa: filtra las consultas por la empresa actual
 * y rellena empresa_id al crear.
 */
trait PerteneceAEmpresa
{
    public static function bootPerteneceAEmpresa(): void
    {
        static::addGlobalScope(new EmpresaScope);

        static::creating(function ($model) {
            if ($model->empresa_id === null) {
                $model->empresa_id = app(EmpresaActual::class)->id();
            }
        });
    }

    /** @return BelongsTo<Empresa, $this> */
    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }
}
