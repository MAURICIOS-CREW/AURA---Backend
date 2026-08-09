<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Expense extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'concept',
        'description',
        'category',
        'amount',
        'expense_date',
        'provider',
        'payment_method',
        'status',
        'receipt',
        'registered_by_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'expense_date' => 'date:Y-m-d',
    ];

    protected $appends = [
        'receipt_url',
    ];

    public function registeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by_id');
    }

    public function getReceiptUrlAttribute(): ?string
    {
        if (empty($this->receipt)) {
            return null;
        }

        if (filter_var($this->receipt, FILTER_VALIDATE_URL)) {
            return $this->receipt;
        }

        return asset('storage/' . ltrim($this->receipt, '/'));
    }
}
