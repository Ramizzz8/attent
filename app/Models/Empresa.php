<?php

namespace App\Models;

use App\Enums\SectorClave;
use App\Sectores\Sector;
use App\Sectores\Sectores;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Empresa extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $attributes = [
        'color_primario' => '#2563eb',
    ];

    protected function casts(): array
    {
        return [
            'sector' => SectorClave::class,
            'ajustes' => 'array',
        ];
    }

    /** Reglas de comportamiento del sector de la empresa. */
    public function motor(): Sector
    {
        return Sectores::para($this->sector);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** @return HasMany<User, $this> */
    public function usuarios(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /** @return HasMany<Cliente, $this> */
    public function clientes(): HasMany
    {
        return $this->hasMany(Cliente::class);
    }
}
