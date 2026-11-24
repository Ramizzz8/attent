<?php

namespace App\Models;

use App\Enums\Prioridad;
use App\Sectores\Sector;
use App\Tenancy\PerteneceAEmpresa;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ticket extends Model
{
    use HasFactory, PerteneceAEmpresa;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'prioridad' => Prioridad::class,
            'datos' => 'array',
            'verificado_en' => 'datetime',
            'sla_vence_en' => 'datetime',
            'sla_pausado_en' => 'datetime',
            'sla_cumplido' => 'boolean',
            'primera_respuesta_vence_en' => 'datetime',
            'primera_respuesta_en' => 'datetime',
            'visita_programada_en' => 'datetime',
            'resuelto_en' => 'datetime',
            'cerrado_en' => 'datetime',
        ];
    }

    public function sector(): Sector
    {
        return $this->empresa->motor();
    }

    /** @return BelongsTo<Cliente, $this> */
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    /** @return BelongsTo<User, $this> */
    public function asignado(): BelongsTo
    {
        return $this->belongsTo(User::class, 'asignado_a');
    }

    /** @return HasMany<TicketEvento, $this> */
    public function eventos(): HasMany
    {
        return $this->hasMany(TicketEvento::class);
    }

    /** @return HasMany<Presupuesto, $this> */
    public function presupuestos(): HasMany
    {
        return $this->hasMany(Presupuesto::class);
    }

    public function presupuestoVigente(): ?Presupuesto
    {
        return $this->presupuestos()->orderByDesc('version')->first();
    }

    /** @param Builder<Ticket> $query */
    public function scopeVerificados(Builder $query): void
    {
        $query->whereNotNull('verificado_en');
    }
}
