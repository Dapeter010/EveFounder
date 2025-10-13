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
        'media_url',
        'view_once',
        'viewed_at',
        'read_at',
        'is_deleted'
    ];

    protected $casts = [
        'read_at' => 'datetime',
        'viewed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'is_deleted' => 'boolean',
        'view_once' => 'boolean'
    ];


    /**
     * Get the match that this message belongs to
     */
    public function match()
    {
        return $this->belongsTo(Matcher::class);
    }

    /**
     * Get the user who sent this message
     */
    public function sender()
    {
//        return $this->belongsTo(UserProfile::class, 'sender_id', 'user_id');
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * Get the user who received this message
     */
    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
//        return $this->belongsTo(UserProfile::class, 'receiver_id', 'user_id');
    }

    /**
     * Scope to get only non-deleted messages
     */
    public function scopeNotDeleted($query)
    {
        return $query->where('is_deleted', false);
    }

    /**
     * Scope to get unread messages
     */
    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    /**
     * Check if message is read
     */
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
