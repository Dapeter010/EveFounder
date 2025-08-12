<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Like extends Model
{
    use HasFactory;

    protected $fillable = [
        'liker_id',
        'liked_id',
        'is_super_like',
        'status',
        'expires_at',
    ];

    protected $casts = [
        'is_super_like' => 'boolean',
        'expires_at' => 'datetime',
    ];

    public function liker(): BelongsTo
    {
        return $this->belongsTo(UserProfile::class, 'liker_id', 'user_id');
    }

    public function liked(): BelongsTo
    {
        return $this->belongsTo(UserProfile::class, 'liked_id', 'user_id');
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function isMatched(): bool
    {
        return $this->status === 'matched';
    }
}