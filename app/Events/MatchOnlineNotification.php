<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MatchOnlineNotification implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public User $user;
    public User $match;

    public function __construct(User $user, User $match)
    {
        $this->user = $user;
        $this->match = $match;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->user->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'notification.matchOnline';
    }

    public function broadcastWith(): array
    {
        return [
            'type' => 'match_online',
            'title' => "{$this->match->first_name} is online",
            'body' => "Say hello! 👋",
            'match' => [
                'id' => $this->match->id,
                'first_name' => $this->match->first_name,
                'photo' => $this->match->photos[0]->url ?? null,
            ],
            'url' => "/messages?match={$this->match->id}",
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
