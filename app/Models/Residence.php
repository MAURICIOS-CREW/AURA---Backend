<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Residence extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_residences')
            ->withPivot('is_primary_owner')
            ->withTimestamps();
    }

    public function address()
    {
        return $this->belongsTo(Address::class, 'address_id');
    }

    public function vehicles()
    {
        return $this->hasMany(Vehicle::class);
    }
}
