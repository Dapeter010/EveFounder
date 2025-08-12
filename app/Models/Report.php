<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Report extends Model
{
    use HasFactory;

    protected $fillable = [
        'reporter_id',
        'reported_id',
        'type',
        'reason',
        'description',
        'evidence',
        'status',
        'admin_notes',
        'handled_by',
        'resolved_at',
    ];

    protected $casts = [
        'evidence' => 'array',
        'resolved_at' => 'datetime',
    ];

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(UserProfile::class, 'reporter_id', 'user_id');
    }

    public function reported(): BelongsTo
    {
        return $this->belongsTo(UserProfile::class, 'reported_id', 'user_id');
    }

    public function handler(): BelongsTo
    {
        return $this->belongsTo(UserProfile::class, 'handled_by', 'user_id');
    }

    public function getSeverityAttribute(): string
    {
        switch ($this->type) {
            case 'harassment':
            case 'inappropriate_behavior':
                return 'high';
            case 'fake_profile':
                return 'medium';
            case 'spam':
            default:
                return 'low';
        }
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isResolved(): bool
    {
        return $this->status === 'resolved';
    }
}