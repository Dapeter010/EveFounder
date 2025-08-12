<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Matcher extends Model
{
    use HasFactory;

    protected $table = 'matches'; // Explicitly set the table name

    protected $fillable = [
        'user1_id',
        'user2_id',
        'matched_at',
        'is_active',
    ];

    protected $casts = [
        'matched_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function user1(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user1_id');
    }

    public function user2(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user2_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function getOtherUser(User $user): User
    {
        return $this->user1_id === $user->id ? $this->user2 : $this->user1;
    }

    public function lastMessage()
    {
        return $this->hasOne(Message::class)->latest();
    }
}
