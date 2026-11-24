<?php

namespace App\Models;

use App\Tenancy\PerteneceAEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vehiculo extends Model
{
    use PerteneceAEmpresa;

    protected $guarded = ['id'];

    /** "1234 abc", "1234-ABC" → "1234ABC" */
    public static function normalizarMatricula(string $matricula): string
    {
        return mb_strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $matricula));
    }

    /** @return BelongsTo<Cliente, $this> */
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    /** @return HasMany<Ticket, $this> */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }
}
