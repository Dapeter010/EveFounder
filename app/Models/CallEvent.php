<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CallEvent extends Model
{
    use HasFactory;

    const UPDATED_AT = null; // Only use created_at

    protected $fillable = [
        'call_id',
        'event_type',
        'user_id',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Get the call associated with this event.
     */
    public function call(): BelongsTo
    {
        return $this->belongsTo(Call::class);
    }

    /**
     * Get the user who triggered this event.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Create an event for a call.
     */
    public static function createEvent(
        int $callId,
        string $eventType,
        ?int $userId = null,
        ?array $metadata = null
    ): self {
        return self::create([
            'call_id' => $callId,
            'event_type' => $eventType,
            'user_id' => $userId,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Check if this is a signaling event (offer, answer, ice_candidate).
     */
    public function isSignalingEvent(): bool
    {
        return in_array($this->event_type, ['offer', 'answer', 'ice_candidate']);
    }

    /**
     * Check if this is a call state event (initiated, accepted, declined, etc.).
     */
    public function isStateEvent(): bool
    {
        return in_array($this->event_type, [
            'initiated',
            'ringing',
            'accepted',
            'declined',
            'missed',
            'ended',
            'failed',
        ]);
    }
}
