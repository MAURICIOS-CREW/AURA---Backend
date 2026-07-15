<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vehicle extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'residence_id',
        'plate',
        'brand',
        'color',
    ];

    public function residence()
    {
        return $this->belongsTo(Residence::class);
    }
}
