<?php

namespace App\Models;

use App\Tenancy\PerteneceAEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Notifications\Notifiable;

/** Proveedor externo de un administrador de fincas (fontanero, ascensorista…). Accede sin cuenta. */
class Proveedor extends Model
{
    use Notifiable, PerteneceAEmpresa;

    protected $table = 'proveedores';

    protected $guarded = ['id'];

    /** @return BelongsToMany<Edificio, $this> */
    public function edificios(): BelongsToMany
    {
        return $this->belongsToMany(Edificio::class, 'edificio_proveedor');
    }

    public function atiende(int $edificioId): bool
    {
        return $this->edificios()->where('edificios.id', $edificioId)->exists();
    }
}
