<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class UserProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'username',
        'phone_number',
        'date_of_birth',
        'gender',
        'sexual_orientation',
        'location',
        'state',
        'country',
        'latitude',
        'longitude',
        'preferred_genders',
        'preferred_age_range',
        'preferred_distance',
        'relationship_goals',
        'height',
        'body_type',
        'ethnicity',
        'hair_color',
        'eye_color',
        'education_level',
        'occupation',
        'religion',
        'drinking_habits',
        'smoking_habits',
        'exercise_frequency',
        'interests',
        'bio',
        'perfect_first_date',
        'favorite_weekend',
        'surprising_fact',
        'photos',
        'registration_date',
        'is_verified',
        'is_active',
        'last_active_at',
        'admin_notes',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'preferred_genders' => 'array',
        'preferred_age_range' => 'array',
        'interests' => 'array',
        'photos' => 'array',
        'registration_date' => 'datetime',
        'is_verified' => 'boolean',
        'is_active' => 'boolean',
        'last_active_at' => 'datetime',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    public function likes(): HasMany
    {
        return $this->hasMany(Like::class, 'liker_id', 'user_id');
    }

    public function receivedLikes(): HasMany
    {
        return $this->hasMany(Like::class, 'liked_id', 'user_id');
    }

    public function sentMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'sender_id', 'user_id');
    }

    public function receivedMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'receiver_id', 'user_id');
    }

    public function profileBoosts(): HasMany
    {
        return $this->hasMany(ProfileBoost::class, 'user_id', 'user_id');
    }

    public function profileViews(): HasMany
    {
        return $this->hasMany(ProfileView::class, 'viewed_id', 'user_id');
    }

    public function blockedUsers(): HasMany
    {
        return $this->hasMany(BlockedUser::class, 'blocker_id', 'user_id');
    }

    public function blockedBy(): HasMany
    {
        return $this->hasMany(BlockedUser::class, 'blocked_id', 'user_id');
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

    public function isOnline(): bool
    {
        return $this->last_active_at && $this->last_active_at->diffInMinutes(now()) <= 15;
    }

    public function distanceFrom(UserProfile $user): float
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

    public function hasActiveSubscription(): bool
    {
        return $this->subscription !== null && $this->subscription->status === 'active';
    }

    public function isPremium(): bool
    {
        return $this->hasActiveSubscription() && $this->subscription->plan_type === 'premium';
    }
}