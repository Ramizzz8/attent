<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Adjunto extends Model
{
    protected $guarded = ['id'];

    /** @return BelongsTo<Ticket, $this> */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /** @return BelongsTo<Mensaje, $this> */
    public function mensaje(): BelongsTo
    {
        return $this->belongsTo(Mensaje::class);
    }

    public function esInterno(): bool
    {
        return (bool) $this->mensaje?->interno;
    }

    /** @return array{id: int, nombre: string, mime: string, tamano: int} */
    public function resumen(): array
    {
        return ['id' => $this->id, 'nombre' => $this->nombre, 'mime' => $this->mime, 'tamano' => $this->tamano];
    }
}
