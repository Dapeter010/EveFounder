<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StripeOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'checkout_session_id',
        'payment_intent_id',
        'customer_id',
        'amount_subtotal',
        'amount_total',
        'currency',
        'payment_status',
        'status',
        'deleted_at',
    ];

    protected $casts = [
        'amount_subtotal' => 'integer',
        'amount_total' => 'integer',
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

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function getAmountInPounds(): float
    {
        return $this->amount_total / 100; // Convert from pence to pounds
    }
}