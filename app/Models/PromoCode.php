<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class PromoCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'description',
        'type',
        'discount_value',
        'duration_in_months',
        'applicable_to',
        'plan_restriction',
        'max_uses',
        'current_uses',
        'starts_at',
        'expires_at',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'duration_in_months' => 'integer',
        'max_uses' => 'integer',
        'current_uses' => 'integer',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    protected $appends = ['is_valid', 'usage_stats'];

    // Relationships
    public function usages(): HasMany
    {
        return $this->hasMany(PromoCodeUsage::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function boosts(): HasMany
    {
        return $this->hasMany(ProfileBoost::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Validation methods
    public function isValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->starts_at && $this->starts_at->isFuture()) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        if ($this->max_uses !== null && $this->current_uses >= $this->max_uses) {
            return false;
        }

        return true;
    }

    public function hasBeenUsedBy(int $userId): bool
    {
        return $this->usages()->where('user_id', $userId)->exists();
    }

    public function canBeUsedBy(int $userId): bool
    {
        if (!$this->isValid()) {
            return false;
        }

        if ($this->hasBeenUsedBy($userId)) {
            return false;
        }

        return true;
    }

    public function canBeAppliedTo(string $type, ?string $planType = null): bool
    {
        // Check if applicable to the requested type
        if ($this->applicable_to !== 'both' && $this->applicable_to !== $type) {
            return false;
        }

        // Check plan restriction
        if ($this->plan_restriction !== null && $planType !== null) {
            if ($this->plan_restriction !== $planType) {
                return false;
            }
        }

        return true;
    }

    // Calculate discount
    public function calculateDiscount(float $originalPrice): float
    {
        if ($this->type === 'percentage') {
            return round($originalPrice * ($this->discount_value / 100), 2);
        } elseif ($this->type === 'fixed_amount') {
            return min($this->discount_value, $originalPrice); // Can't discount more than the price
        } elseif ($this->type === 'free_trial') {
            return $originalPrice; // Full discount for free trial
        }

        return 0;
    }

    public function getFinalPrice(float $originalPrice): float
    {
        $discount = $this->calculateDiscount($originalPrice);
        return max(0, $originalPrice - $discount);
    }

    // Apply promo code (increment usage counter)
    public function apply(int $userId, ?int $subscriptionId = null, ?int $boostId = null): PromoCodeUsage
    {
        $originalPrice = 0;

        if ($subscriptionId) {
            $subscription = Subscription::find($subscriptionId);
            $originalPrice = $subscription ? (float)$subscription->amount : 0;
        } elseif ($boostId) {
            $boost = ProfileBoost::find($boostId);
            $originalPrice = $boost ? (float)$boost->cost : 0;
        }

        $discountAmount = $this->calculateDiscount($originalPrice);

        // Increment usage counter
        $this->increment('current_uses');

        // Create usage record
        return $this->usages()->create([
            'user_id' => $userId,
            'subscription_id' => $subscriptionId,
            'boost_id' => $boostId,
            'discount_amount' => $discountAmount,
            'used_at' => now(),
        ]);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>=', now());
            })
            ->where(function ($q) {
                $q->whereNull('max_uses')
                    ->orWhereRaw('current_uses < max_uses');
            });
    }

    public function scopeApplicableTo($query, string $type)
    {
        return $query->where(function ($q) use ($type) {
            $q->where('applicable_to', $type)
                ->orWhere('applicable_to', 'both');
        });
    }

    // Attributes
    public function getIsValidAttribute(): bool
    {
        return $this->isValid();
    }

    public function getUsageStatsAttribute(): array
    {
        return [
            'total_uses' => $this->current_uses,
            'max_uses' => $this->max_uses,
            'remaining_uses' => $this->max_uses ? max(0, $this->max_uses - $this->current_uses) : null,
            'revenue_lost' => $this->usages()->sum('discount_amount'),
            'subscription_uses' => $this->usages()->whereNotNull('subscription_id')->count(),
            'boost_uses' => $this->usages()->whereNotNull('boost_id')->count(),
        ];
    }
}
