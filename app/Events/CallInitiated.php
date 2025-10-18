<?php

namespace App\Events;

use App\Models\Call;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CallInitiated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $call;
    public $caller;

    /**
     * Create a new event instance.
     */
    public function __construct(Call $call, User $caller)
    {
        $this->call = $call;
        $this->caller = $caller;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->call->receiver_id),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'call.initiated';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'call_id' => $this->call->id,
            'match_id' => $this->call->match_id,
            'type' => $this->call->type,
            'caller' => [
                'id' => $this->caller->id,
                'first_name' => $this->caller->first_name,
                'last_name' => $this->caller->last_name,
                'primary_photo_url' => $this->caller->primary_photo_url,
            ],
        ];
    }
}
