<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfileView extends Model
{
    use HasFactory;

    protected $fillable = [
        'viewer_id',
        'viewed_id',
        'viewed_at',
    ];

    protected $casts = [
        'viewed_at' => 'datetime',
    ];

    public function viewer(): BelongsTo
    {
        return $this->belongsTo(UserProfile::class, 'viewer_id', 'user_id');
    }

    public function viewed(): BelongsTo
    {
        return $this->belongsTo(UserProfile::class, 'viewed_id', 'user_id');
    }
}