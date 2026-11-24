<?php

namespace App\Models;

use App\Tenancy\PerteneceAEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Equipo extends Model
{
    use PerteneceAEmpresa;

    public const TIPOS = [
        'portatil' => 'Portátil',
        'sobremesa' => 'Sobremesa',
        'impresora' => 'Impresora',
        'proyector' => 'Proyector',
        'red' => 'Equipo de red',
        'otro' => 'Otro',
    ];

    protected $guarded = ['id'];

    public static function normalizarCodigo(string $codigo): string
    {
        return mb_strtoupper(trim($codigo));
    }

    /** @return HasMany<Ticket, $this> */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }
}
