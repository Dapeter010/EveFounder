<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlockedUser extends Model
{
    use HasFactory;

    protected $fillable = [
        'blocker_id',
        'blocked_id',
        'reason',
    ];

    public function blocker(): BelongsTo
    {
        return $this->belongsTo(UserProfile::class, 'blocker_id', 'user_id');
    }

    public function blocked(): BelongsTo
    {
        return $this->belongsTo(UserProfile::class, 'blocked_id', 'user_id');
    }
}