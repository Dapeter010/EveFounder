<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class UserProfile extends Model
{
    use HasFactory, SoftDeletes;

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
        'notifications',
        'privacy_settings',
        'visibility_settings',
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
        'notifications' => 'array',
        'privacy_settings' => 'array',
        'visibility_settings' => 'array',
        'registration_date' => 'datetime',
        'is_verified' => 'boolean',
        'is_active' => 'boolean',
        'last_active_at' => 'datetime',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
        'registration_date',
        'last_active_at',
    ];



    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(UserPhoto::class, 'user_id', 'user_id')->orderBy('order');
    }

    public function getPhotosAttribute($value)
    {
        // For backward compatibility: if accessing photos directly, load from relationship
        if (!$this->relationLoaded('photos')) {
            $this->load('photos');
        }

        // Return photos from user_photos table
        return $this->getRelation('photos')->toArray();
    }


    public function subscription()
    {
        return $this->hasOne(Subscription::class, 'user_id', 'user_id');
    }

    // Default settings methods
    public function getDefaultNotifications()
    {
        return [
            'new_matches' => true,
            'messages' => true,
            'likes' => false,
            'marketing' => false,
        ];
    }

    public function getDefaultPrivacySettings()
    {
        return [
            'show_age' => true,
            'show_distance' => true,
            'online_status' => true,
            'read_receipts' => true,
        ];
    }

    public function getDefaultVisibilitySettings()
    {
        return [
            'show_me' => true,
        ];
    }

    // Mutators to ensure defaults
    public function setNotificationsAttribute($value)
    {
        $defaults = $this->getDefaultNotifications();
        $this->attributes['notifications'] = json_encode(array_merge($defaults, $value ?? []));
    }

    public function setPrivacySettingsAttribute($value)
    {
        $defaults = $this->getDefaultPrivacySettings();
        $this->attributes['privacy_settings'] = json_encode(array_merge($defaults, $value ?? []));
    }

    public function setVisibilitySettingsAttribute($value)
    {
        $defaults = $this->getDefaultVisibilitySettings();
        $this->attributes['visibility_settings'] = json_encode(array_merge($defaults, $value ?? []));
    }

    // Accessors
    public function getNotificationsAttribute($value)
    {
        $decoded = json_decode($value, true) ?? [];
        return array_merge($this->getDefaultNotifications(), $decoded);
    }

    public function getPrivacySettingsAttribute($value)
    {
        $decoded = json_decode($value, true) ?? [];
        return array_merge($this->getDefaultPrivacySettings(), $decoded);
    }

    public function getVisibilitySettingsAttribute($value)
    {
        $decoded = json_decode($value, true) ?? [];
        return array_merge($this->getDefaultVisibilitySettings(), $decoded);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeVisible($query)
    {
        return $query->active()
            ->where(function ($q) {
                $q->whereJsonContains('visibility_settings->show_me', true)
                    ->orWhereNull('visibility_settings');
            });
    }

    // Helper methods
    public function getAge()
    {
        return $this->date_of_birth ? $this->date_of_birth->age : null;
    }

    public function getPrimaryPhoto()
    {
        // Load photos relationship if not already loaded
        if (!$this->relationLoaded('photos')) {
            $this->load('photos');
        }

        // Get primary photo from user_photos table
        $primary = $this->photos()->where('is_primary', true)->first();

        if ($primary) {
            return $primary;
        }

        // Fallback to first photo if no primary set
        return $this->photos()->orderBy('order')->first();
    }

    public function updateLastActive()
    {
        $this->update(['last_active_at' => now()]);
    }

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


//    public function isOnline()
//    {
//        if (!$this->last_active_at) {
//            return false;
//        }
//
//        return $this->last_active_at->gt(now()->subMinutes(5));
//    }

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
