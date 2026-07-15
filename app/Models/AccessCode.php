<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccessCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'residence_id',
        'guest_name',
        'code',
        'type',
        'valid_from',
        'valid_until',
        'uses',
    ];

    protected $casts = [
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
        'uses' => 'integer',
    ];

    public function residence()
    {
        return $this->belongsTo(Residence::class);
    }
}
