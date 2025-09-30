<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'plan_type',
        'status',
        'amount',
        'currency',
        'price_id',
        'stripe_subscription_id',
        'starts_at',
        'ends_at',
        'cancelled_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && $this->ends_at->isFuture();
    }

    public function isExpired(): bool
    {
        return $this->ends_at->isPast();
    }

    public function daysRemaining(): int
    {
        return max(0, $this->ends_at->diffInDays(now()));
    }

    public function isPremium(): bool
    {
        if (!$this->isActive()) {
            return false;
        }

        // Check if price_id matches Premium plan
        $premiumPriceId = 'price_1RvJUZGhlS5RvknCyv6vX6lT';
        return $this->price_id === $premiumPriceId;
    }

    public function isBasic(): bool
    {
        if (!$this->isActive()) {
            return false;
        }

        // Check if price_id matches Basic plan
        $basicPriceId = 'price_1RvJSxGhlS5RvknCWKGwanha';
        return $this->price_id === $basicPriceId;
    }

    public function isFree(): bool
    {
        return !$this->isActive() || (!$this->isPremium() && !$this->isBasic());
    }
}