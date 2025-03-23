<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;

    public function __construct(Message $message)
    {
        $this->message = $message;
    }

    public function broadcastOn()
    {
        // Si es un mensaje directo
        if ($this->message->receiver_id) {
            return [
                new PrivateChannel('chat.' . $this->message->sender_id . '.' . $this->message->receiver_id),
                new PrivateChannel('chat.' . $this->message->receiver_id . '.' . $this->message->sender_id)
            ];
        }
        
        // Si es un mensaje de grupo
        if ($this->message->group_id) {
            return new PrivateChannel('group.' . $this->message->group_id);
        }
    }
}