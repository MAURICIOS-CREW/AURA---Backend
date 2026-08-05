<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

class AccessCode extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'residence_id',
        'contracted_service_id',
        'user_id',
        'guest_name',
        'code',
        'type',
        'valid_from',
        'valid_until',
        'uses',
        'max_uses',
        'active_days',
        'start_time',
        'end_time',
        'is_active',
    ];

    protected $casts = [
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
        'uses' => 'integer',
        'max_uses' => 'integer',
        'active_days' => 'array',
        'is_active' => 'boolean',
    ];

    public function residence(): BelongsTo
    {
        return $this->belongsTo(Residence::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function contractedService(): BelongsTo
    {
        return $this->belongsTo(ContractedService::class, 'contracted_service_id');
    }

    /**
     * Scope para filtrar por residencias.
     */
    public function scopeForResidences(Builder $query, array|Collection $residenceIds): Builder
    {
        return $query->whereIn('residence_id', $residenceIds);
    }

    /**
     * Scope para filtrar por tipo de código ('custom', 'service', 'temp', etc.).
     */
    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    /**
     * Scope para verificar si la vigencia o la fecha programada es hoy.
     */
    public function scopeScheduledForToday(Builder $query): Builder
    {
        $today = now()->toDateString();

        return $query->whereDate('valid_from', $today)
            ->orWhereHas('contractedService', fn (Builder $cs) => $cs->whereDate('exact_scheduled_at', $today));
    }

    /**
     * Scope para obtener únicamente los servicios programados para hoy.
     */
    public function scopeServicesForToday(Builder $query): Builder
    {
        return $query->ofType('service')->where(fn (Builder $q) => $q->scheduledForToday());
    }

    /**
     * Scope compuesto para obtener todos los códigos visibles para el residente:
     * - QRs de invitados personalizados ('custom')
     * - QRs de servicios contratados programados para el día de hoy ('service')
     */
    public function scopeVisibleForResident(Builder $query, array|Collection $residenceIds): Builder
    {
        return $query->forResidences($residenceIds)
            ->where(function (Builder $q) {
                $q->ofType('custom')
                  ->orWhere(fn (Builder $sq) => $sq->servicesForToday());
            });
    }
}
