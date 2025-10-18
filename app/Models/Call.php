<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Call extends Model
{
    use HasFactory;

    protected $fillable = [
        'match_id',
        'caller_id',
        'receiver_id',
        'type',
        'status',
        'started_at',
        'ended_at',
        'duration',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the match associated with this call.
     */
    public function match(): BelongsTo
    {
        return $this->belongsTo(Matcher::class);
    }

    /**
     * Get the caller (user who initiated the call).
     */
    public function caller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'caller_id');
    }

    /**
     * Get the receiver (user who received the call).
     */
    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    /**
     * Get all events for this call.
     */
    public function events(): HasMany
    {
        return $this->hasMany(CallEvent::class);
    }

    /**
     * Mark call as started.
     */
    public function markAsStarted(): void
    {
        $this->update([
            'status' => 'ongoing',
            'started_at' => now(),
        ]);
    }

    /**
     * Mark call as ended and calculate duration.
     */
    public function markAsEnded(): void
    {
        $endedAt = now();
        $duration = null;

        if ($this->started_at) {
            $duration = $endedAt->diffInSeconds($this->started_at);
        }

        $this->update([
            'status' => 'ended',
            'ended_at' => $endedAt,
            'duration' => $duration,
        ]);
    }

    /**
     * Mark call as missed.
     */
    public function markAsMissed(): void
    {
        $this->update([
            'status' => 'missed',
            'ended_at' => now(),
        ]);
    }

    /**
     * Mark call as declined.
     */
    public function markAsDeclined(): void
    {
        $this->update([
            'status' => 'declined',
            'ended_at' => now(),
        ]);
    }

    /**
     * Check if the call is still active (ringing or ongoing).
     */
    public function isActive(): bool
    {
        return in_array($this->status, ['ringing', 'ongoing']);
    }

    /**
     * Get the other user in the call (relative to the given user).
     */
    public function getOtherUser(int $userId): ?User
    {
        if ($this->caller_id === $userId) {
            return $this->receiver;
        } elseif ($this->receiver_id === $userId) {
            return $this->caller;
        }

        return null;
    }
}
