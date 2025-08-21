<?php

namespace Clarus\SecureChat\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Clarus\SecureChat\Models\ChatMessage;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class ChatMessageSaved implements ShouldBroadcast
{
    use InteractsWithSockets, SerializesModels;

    /**
     * The name of the queue on which to place the event.
     *
     * @var string
     */
    public $broadcastQueue;

    /**
     * @var ChatMessage
     */
    public $message;

    /**
     * ChatMessageCreated constructor.
     *
     * @param  ChatMessage  $message
     */
    public function __construct(ChatMessage $message)
    {
        $this->message = $message;
        $this->broadcastQueue = env('SQS_SECURE_CHAT_QUEUE_URL', 'secure-chat');
    }

    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs()
    {
        return 'chat_message';
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        $chatRoom = $this->message->chatRoom;

        $channels = $chatRoom->users->map(function ($user) {
            return new PrivateChannel($user->chat_channel_name);
        })->toArray();

        return $channels ?: new PrivateChannel('overflow_channel');
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith()
    {
        return [
            'chat_room_id' => $this->message->chat_room_id,
            'message_id'   => $this->message->id,
            'type'         => $this->message->type,
        ];
    }
}
