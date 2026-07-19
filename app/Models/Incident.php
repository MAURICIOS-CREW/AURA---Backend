<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Incident extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'reporter_user_id',
        'title',
        'description',
        'status',
    ];

    public function reporter(): BelongsTo {
        return $this->belongsTo(User::class, 'reporter_user_id');
    }

    public function comments(): HasMany {
        return $this->hasMany(IncidentComment::class);
    }
}
