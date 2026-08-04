<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class FinancialCharge extends Model
{
    use HasFactory;

    protected $fillable = [
        'residence_id',
        'amount',
        'month',
        'year',
        'status',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'month' => 'integer',
        'year' => 'integer',
    ];

    public function residence(): BelongsTo
    {
        return $this->belongsTo(Residence::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'charge_id');
    }

    public function contractedService(): HasOne
    {
        return $this->hasOne(ContractedService::class, 'charge_id');
    }
}
