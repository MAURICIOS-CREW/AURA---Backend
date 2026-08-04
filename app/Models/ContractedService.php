<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContractedService extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'service_id',
        'user_id',
        'residence_id',
        'charge_id',
        'preferred_date',
        'visit_time_from',
        'visit_time_to',
        'exact_scheduled_at',
        'amount',
        'status',
        'notes',
        'payment_method',
        'stripe_payment_id',
    ];

    protected $casts = [
        'preferred_date' => 'date:Y-m-d',
        'exact_scheduled_at' => 'datetime',
        'amount' => 'decimal:2',
    ];

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function residence(): BelongsTo
    {
        return $this->belongsTo(Residence::class);
    }

    public function financialCharge(): BelongsTo
    {
        return $this->belongsTo(FinancialCharge::class, 'charge_id');
    }

    public function accessCode(): HasOne
    {
        return $this->hasOne(AccessCode::class, 'contracted_service_id');
    }
}
