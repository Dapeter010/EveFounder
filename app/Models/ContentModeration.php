<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentModeration extends Model
{
    use HasFactory;

    protected $table = 'content_moderation';

    protected $fillable = [
        'user_id',
        'content_type',
        'content_url',
        'content_text',
        'status',
        'ai_score',
        'ai_flags',
        'admin_notes',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'ai_flags' => 'array',
        'ai_score' => 'decimal:2',
        'reviewed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(UserProfile::class, 'user_id', 'user_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(UserProfile::class, 'reviewed_by', 'user_id');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isFlagged(): bool
    {
        return $this->status === 'flagged';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }
}