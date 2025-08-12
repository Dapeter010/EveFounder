<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StripeSubscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'subscription_id',
        'price_id',
        'current_period_start',
        'current_period_end',
        'cancel_at_period_end',
        'payment_method_brand',
        'payment_method_last4',
        'status',
        'deleted_at',
    ];

    protected $casts = [
        'current_period_start' => 'integer',
        'current_period_end' => 'integer',
        'cancel_at_period_end' => 'boolean',
        'deleted_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(StripeCustomer::class, 'customer_id', 'customer_id');
    }

    public function userProfile(): BelongsTo
    {
        return $this->belongsTo(UserProfile::class, 'customer_id', 'customer_id')
            ->through('customer');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isPastDue(): bool
    {
        return $this->status === 'past_due';
    }

    public function isCanceled(): bool
    {
        return $this->status === 'canceled';
    }

    public function getCurrentPeriodEnd(): ?\DateTime
    {
        return $this->current_period_end 
            ? new \DateTime('@' . $this->current_period_end)
            : null;
    }

    public function getCurrentPeriodStart(): ?\DateTime
    {
        return $this->current_period_start 
            ? new \DateTime('@' . $this->current_period_start)
            : null;
    }
}