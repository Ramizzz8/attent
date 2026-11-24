<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Mensaje extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['interno' => 'boolean'];
    }

    /** @return BelongsTo<Ticket, $this> */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /** Empleado (User), cliente (Cliente) o proveedor externo (Proveedor). */
    public function autor(): MorphTo
    {
        return $this->morphTo()->withoutGlobalScopes();
    }

    /** @return HasMany<Adjunto, $this> */
    public function adjuntos(): HasMany
    {
        return $this->hasMany(Adjunto::class);
    }

    public function esDelEquipo(): bool
    {
        return $this->autor_type === (new User)->getMorphClass();
    }
}
