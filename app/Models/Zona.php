<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Zona extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['prioridad_alta' => 'boolean'];
    }

    /** @return BelongsTo<Edificio, $this> */
    public function edificio(): BelongsTo
    {
        return $this->belongsTo(Edificio::class);
    }
}
