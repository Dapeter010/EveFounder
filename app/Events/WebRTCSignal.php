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

class WebRTCSignal implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $call;
    public $fromUser;
    public $signalType;
    public $signalData;

    /**
     * Create a new event instance.
     */
    public function __construct(Call $call, User $fromUser, string $signalType, $signalData)
    {
        $this->call = $call;
        $this->fromUser = $fromUser;
        $this->signalType = $signalType;
        $this->signalData = $signalData;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        // Determine the recipient (the other user in the call)
        $recipientId = $this->call->caller_id === $this->fromUser->id
            ? $this->call->receiver_id
            : $this->call->caller_id;

        return [
            new PrivateChannel('user.' . $recipientId),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'call.signal';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'call_id' => $this->call->id,
            'from_user_id' => $this->fromUser->id,
            'signal_type' => $this->signalType,
            'signal_data' => $this->signalData,
        ];
    }
}
