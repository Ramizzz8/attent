<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Presupuesto extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'iva_pct' => 'decimal:2',
            'respondido_en' => 'datetime',
        ];
    }

    /** @return BelongsTo<Ticket, $this> */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /** @return HasMany<PresupuestoLinea, $this> */
    public function lineas(): HasMany
    {
        return $this->hasMany(PresupuestoLinea::class);
    }

    public function base(): float
    {
        return round($this->lineas->sum(fn (PresupuestoLinea $l) => (float) $l->cantidad * (float) $l->precio_unitario), 2);
    }

    public function iva(): float
    {
        return round($this->base() * (float) $this->iva_pct / 100, 2);
    }

    public function total(): float
    {
        return round($this->base() + $this->iva(), 2);
    }

    /** @return array<string, mixed> */
    public function resumen(): array
    {
        return [
            'id' => $this->id,
            'version' => $this->version,
            'estado' => $this->estado,
            'ivaPct' => (float) $this->iva_pct,
            'base' => $this->base(),
            'iva' => $this->iva(),
            'total' => $this->total(),
            'comentario' => $this->comentario_cliente,
            'respondido' => $this->respondido_en?->toIso8601String(),
            'creado' => $this->created_at->toIso8601String(),
            'lineas' => $this->lineas->map(fn (PresupuestoLinea $l) => [
                'tipo' => $l->tipo,
                'concepto' => $l->concepto,
                'cantidad' => (float) $l->cantidad,
                'precio' => (float) $l->precio_unitario,
                'importe' => round((float) $l->cantidad * (float) $l->precio_unitario, 2),
            ]),
        ];
    }
}
