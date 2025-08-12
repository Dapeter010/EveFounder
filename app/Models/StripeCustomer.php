<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StripeCustomer extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'customer_id',
        'deleted_at',
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
    ];

    public function userProfile(): HasOne
    {
        return $this->hasOne(UserProfile::class, 'user_id', 'user_id');
    }

    public function subscription(): HasOne
    {
        return $this->hasOne(StripeSubscription::class, 'customer_id', 'customer_id')
            ->whereNull('deleted_at');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(StripeSubscription::class, 'customer_id', 'customer_id')
            ->whereNull('deleted_at');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(StripeOrder::class, 'customer_id', 'customer_id')
            ->whereNull('deleted_at');
    }
}