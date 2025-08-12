<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'min_age',
        'max_age',
        'max_distance',
        'interested_genders',
        'min_height',
        'max_height',
        'education_preferences',
        'profession_preferences',
        'show_age',
        'show_distance',
        'show_online_status',
        'show_read_receipts',
    ];

    protected $casts = [
        'interested_genders' => 'array',
        'education_preferences' => 'array',
        'profession_preferences' => 'array',
        'show_age' => 'boolean',
        'show_distance' => 'boolean',
        'show_online_status' => 'boolean',
        'show_read_receipts' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}