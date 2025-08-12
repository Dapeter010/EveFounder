<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'match_id',
        'sender_id',
        'receiver_id',
        'content',
        'type',
        'read_at',
        'is_deleted',
    ];

    protected $casts = [
        'read_at' => 'datetime',
        'is_deleted' => 'boolean',
    ];


    public function sender(): BelongsTo
    {
        return $this->belongsTo(UserProfile::class, 'sender_id', 'user_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(UserProfile::class, 'receiver_id', 'user_id');
    }

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    public function markAsRead(): void
    {
        if (!$this->isRead()) {
            $this->update(['read_at' => now()]);
        }
    }
}