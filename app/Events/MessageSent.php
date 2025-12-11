<?php

namespace App\Events;

use App\Models\ChatMessage;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public ChatMessage $message;

    public function __construct(ChatMessage $message)
    {
        $this->message = $message->load('user');
    }

    public function broadcastOn(): Channel|array
    {
        return new PrivateChannel('conversations.' . $this->message->conversation_id);
    }

    public function broadcastAs(): string
    {
        return 'MessageSent';
    }

    public function broadcastWith(): array
    {
        return [
            'id'              => $this->message->id,
            'conversation_id' => $this->message->conversation_id,
            'message'         => $this->message->message,
            'type'            => $this->message->type,
            'file_path'       => $this->message->file_path,
            'created_at'      => optional($this->message->created_at)->format('d/m/Y H:i'),
            'sender_id'       => $this->message->user_id,
            'sender_name'     => $this->message->sender_name,
        ];
    }
}
