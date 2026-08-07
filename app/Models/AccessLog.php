<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccessLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'access_type',
        'method',
        'residence_id',
        'vehicle_id',
        'access_code_id',
        'guard_user_id',
        'status',
        'ai_confidence',
        'timestamp',
        'message',
        'device_identifier',
        'scanned_code',
    ];

    public function accessCode()
    {
        return $this->belongsTo(AccessCode::class)->withTrashed();
    }

    public function residence()
    {
        return $this->belongsTo(Residence::class);
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class)->withTrashed();
    }
}
