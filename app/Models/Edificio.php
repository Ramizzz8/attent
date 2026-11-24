<?php

namespace App\Models;

use App\Tenancy\PerteneceAEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Edificio extends Model
{
    use PerteneceAEmpresa;

    protected $guarded = ['id'];

    /** @return HasMany<Zona, $this> */
    public function zonas(): HasMany
    {
        return $this->hasMany(Zona::class)->orderBy('id');
    }

    /** @return BelongsToMany<Proveedor, $this> */
    public function proveedores(): BelongsToMany
    {
        return $this->belongsToMany(Proveedor::class, 'edificio_proveedor');
    }

    /** Slug único dentro de la empresa: "residencial-mar", "residencial-mar-2"… */
    public static function slugLibre(int $empresaId, string $nombre): string
    {
        $base = Str::slug($nombre) ?: 'edificio';
        $slug = $base;
        for ($i = 2; static::withoutGlobalScopes()->where('empresa_id', $empresaId)->where('slug', $slug)->exists(); $i++) {
            $slug = "{$base}-{$i}";
        }

        return $slug;
    }
}
