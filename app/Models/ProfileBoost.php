<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfileBoost extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'boost_type',
        'cost',
        'starts_at',
        'ends_at',
        'views_gained',
        'likes_gained',
        'matches_gained',
        'status',
    ];

    protected $casts = [
        'cost' => 'decimal:2',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(UserProfile::class, 'user_id', 'user_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && 
               $this->starts_at->isPast() && 
               $this->ends_at->isFuture();
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed' || $this->ends_at->isPast();
    }

    public function getDurationInHours(): int
    {
        return $this->starts_at->diffInHours($this->ends_at);
    }
}