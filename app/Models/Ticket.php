<?php

namespace App\Models;

use App\Enums\Prioridad;
use App\Sectores\Sector;
use App\Tenancy\PerteneceAEmpresa;
use App\Tickets\Sla;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

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
            'sla_pausa_segundos' => 'integer',
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

    /** @return HasMany<Mensaje, $this> */
    public function mensajes(): HasMany
    {
        return $this->hasMany(Mensaje::class);
    }

    /** @return HasMany<Adjunto, $this> */
    public function adjuntos(): HasMany
    {
        return $this->hasMany(Adjunto::class);
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

    /**
     * Clientes que reciben los avisos del ticket (el que lo abrió y, en fincas, los vecinos afectados).
     *
     * @return Collection<int, Cliente>
     */
    public function clientesAvisables(): Collection
    {
        return collect([$this->cliente])->filter()->values();
    }

    public function estadoSla(): ?string
    {
        return app(Sla::class)->estado($this);
    }

    public function etiquetaEstado(): string
    {
        return $this->sector()->estados()[$this->estado] ?? $this->estado;
    }

    /** @param Builder<Ticket> $query */
    public function scopeVerificados(Builder $query): void
    {
        $query->whereNotNull('verificado_en');
    }

    /** @param Builder<Ticket> $query */
    public function scopeAbiertos(Builder $query, Sector $sector): void
    {
        $query->whereNotIn('estado', $sector->estadosTerminados());
    }
}
