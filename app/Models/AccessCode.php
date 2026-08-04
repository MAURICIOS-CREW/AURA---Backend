<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\SoftDeletes;

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

    public function residence()
    {
        return $this->belongsTo(Residence::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function contractedService()
    {
        return $this->belongsTo(ContractedService::class, 'contracted_service_id');
    }
}
