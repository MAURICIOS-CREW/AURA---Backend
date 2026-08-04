<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'charge_id',
        'user_id',
        'amount',
        'payment_method',
        'receipt',
        'validator_admin_id',
        'status',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function financialCharge(): BelongsTo
    {
        return $this->belongsTo(FinancialCharge::class, 'charge_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function validatorAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validator_admin_id');
    }
}
