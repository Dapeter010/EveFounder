<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'email',
        'password',
        'first_name',
        'last_name',
        'date_of_birth',
        'gender',
        'location',
        'bio',
        'interests',
        'is_verified',
        'is_active',
        'last_active_at',
        'latitude',
        'longitude',
        'height',
        'education',
        'profession',
        'relationship_type',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'date_of_birth' => 'date',
        'last_active_at' => 'datetime',
        'interests' => 'array',
        'is_verified' => 'boolean',
        'is_active' => 'boolean',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            if (empty($user->uid)) {
                $user->uid = (string)\Illuminate\Support\Str::uuid();
            }
        });
    }


    // Relationships
    public function photos(): HasMany
    {
        return $this->hasMany(UserPhoto::class);
    }

    public function preferences(): HasOne
    {
        return $this->hasOne(UserPreference::class);
    }

    public function subscription(): HasOne
    {
        return $this->hasOne(Subscription::class)->where('status', 'active');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function sentLikes(): HasMany
    {
        return $this->hasMany(Like::class, 'liker_id', 'id');
    }

    public function receivedLikes(): HasMany
    {
        return $this->hasMany(Like::class, 'liked_id', 'id');
    }

    public function matches(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'matches', 'user1_id', 'user2_id', 'id', 'id')
            ->withPivot('matched_at', 'is_active')
            ->wherePivot('is_active', true);
    }

    public function sentMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'sender_id', 'id');
    }

    public function receivedMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'receiver_id', 'id');
    }

    public function profileBoosts(): HasMany
    {
        return $this->hasMany(ProfileBoost::class, 'user_id', 'id');
    }

    public function profileViews(): HasMany
    {
        return $this->hasMany(ProfileView::class, 'viewed_id', 'id');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'user_id', 'id');
    }

    public function userProfile(): HasOne
    {
        return $this->hasOne(UserProfile::class, 'user_id', 'id');
    }

    // Helper methods
    public function getAgeAttribute(): int
    {
        return $this->date_of_birth->age;
    }

    public function getFullNameAttribute(): string
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    public function hasActiveSubscription(): bool
    {
        return $this->subscription !== null;
    }

    public function isPremium(): bool
    {
        return $this->hasActiveSubscription() && $this->subscription->plan_type === 'premium';
    }

    public function isOnline(): bool
    {
        return $this->last_active_at && $this->last_active_at->diffInMinutes(now()) <= 15;
    }

    public function distanceFrom(User $user): float
    {
        if (!$this->latitude || !$this->longitude || !$user->latitude || !$user->longitude) {
            return 0;
        }

        $earthRadius = 3959; // miles
        $latDelta = deg2rad($user->latitude - $this->latitude);
        $lonDelta = deg2rad($user->longitude - $this->longitude);

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
            cos(deg2rad($this->latitude)) * cos(deg2rad($user->latitude)) *
            sin($lonDelta / 2) * sin($lonDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
