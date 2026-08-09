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
        'stripe_payment_intent_id',
        'stripe_payment_method_id',
        'failure_code',
        'failure_reason',
        'receipt_url',
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

    /**
     * receipt_url puede venir ya como URL absoluta (recibo hosteado por Stripe) o,
     * para transferencias, quedar vacía mientras el archivo real vive en `receipt`
     * (path relativo al disco público). Se resuelve aquí para que el cliente
     * siempre reciba una URL servible sin tener que conocer la convención de storage.
     */
    public function getReceiptUrlAttribute($value)
    {
        if (!empty($value) && filter_var($value, FILTER_VALIDATE_URL)) {
            return $value;
        }

        if (!empty($this->attributes['receipt'])) {
            return asset('storage/' . ltrim($this->attributes['receipt'], '/'));
        }

        return $value;
    }
}
