<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DarkModeSettings extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'invisible_mode',
        'ghost_mode',
        'location_obfuscation_enabled',
        'location_obfuscation_radius',
        'screenshot_prevention',
        'auto_delete_messages',
        'auto_delete_delay',
    ];

    protected $casts = [
        'invisible_mode' => 'boolean',
        'ghost_mode' => 'boolean',
        'location_obfuscation_enabled' => 'boolean',
        'screenshot_prevention' => 'boolean',
        'auto_delete_messages' => 'boolean',
        'location_obfuscation_radius' => 'integer',
        'auto_delete_delay' => 'integer',
    ];

    /**
     * Get the user that owns the settings
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get or create settings for a user
     */
    public static function getOrCreateForUser(int $userId): self
    {
        return static::firstOrCreate(
            ['user_id' => $userId],
            [
                'invisible_mode' => false,
                'ghost_mode' => false,
                'location_obfuscation_enabled' => false,
                'location_obfuscation_radius' => 5,
                'screenshot_prevention' => false,
                'auto_delete_messages' => false,
                'auto_delete_delay' => 30,
            ]
        );
    }

    /**
     * Check if any privacy features are enabled
     */
    public function hasAnyFeatureEnabled(): bool
    {
        return $this->invisible_mode
            || $this->ghost_mode
            || $this->location_obfuscation_enabled
            || $this->screenshot_prevention
            || $this->auto_delete_messages;
    }

    /**
     * Get obfuscated coordinates
     */
    public function obfuscateLocation(float $latitude, float $longitude): array
    {
        if (!$this->location_obfuscation_enabled) {
            return ['latitude' => $latitude, 'longitude' => $longitude];
        }

        // Convert radius from km to degrees (approximate)
        $radiusInDegrees = $this->location_obfuscation_radius / 111.32; // 1 degree ≈ 111.32 km

        // Generate random offset within radius
        $angle = mt_rand(0, 360) * (M_PI / 180);
        $distance = (mt_rand(0, 100) / 100) * $radiusInDegrees;

        $latOffset = $distance * sin($angle);
        $lonOffset = $distance * cos($angle);

        return [
            'latitude' => round($latitude + $latOffset, 6),
            'longitude' => round($longitude + $lonOffset, 6),
        ];
    }
}
