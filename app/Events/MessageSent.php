<?php

namespace App\Events;

use App\Models\Message;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

//use Illuminate\Broadcasting\PresenceChannel;
//use Illuminate\Broadcasting\Channel;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;
    public $sender;

    /**
     * Create a new event instance.
     */
    public function __construct(Message $message, User $sender)
    {
        $this->message = $message;
        $this->sender = $sender;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return PrivateChannel
     */
    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('match.' . $this->message->match_id);

    }

    public function broadcastWith()
    {
        return [
            'message' => [
                'id' => $this->message->id,
                'content' => $this->message->content,
                'sender_id' => $this->message->sender_id,
                'match_id' => $this->message->match_id,
                'type' => $this->message->type,
                'created_at' => $this->message->created_at->toISOString(),
                'read_at' => $this->message->read_at?->toISOString(),
            ],
            'sender' => [
                'id' => $this->sender->id,
                'name' => $this->sender->first_name,
                'avatar' => $this->sender->photos->first()?->photo_url,
            ],
            'type' => 'new_message',
        ];
    }

    public function broadcastAs()
    {
        return 'message.sent';
    }
}
