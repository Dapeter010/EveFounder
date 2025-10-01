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

class NewMatchNotification implements ShouldBroadcast
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
        return 'notification.newMatch';
    }

    public function broadcastWith(): array
    {
        return [
            'type' => 'new_match',
            'title' => "It's a Match! 💕",
            'body' => "You and {$this->match->first_name} liked each other!",
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
