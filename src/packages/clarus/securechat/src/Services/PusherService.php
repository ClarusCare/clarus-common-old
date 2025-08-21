<?php

namespace Clarus\SecureChat\Services;

use Pusher\Pusher;
use App\Models\User;
use Clarus\SecureChat\Models\ChatRoom;
use Clarus\SecureChat\Models\ChatEvent;
use Clarus\SecureChat\Models\ChatMessage;
use Illuminate\Broadcasting\PrivateChannel;

class PusherService
{
    /**
     * @var Pusher
     */
    protected $pusher;

    public function __construct()
    {
        $this->pusher = new Pusher(
            config('secure-chat.pusher_app_key'),
            config('secure-chat.pusher_app_secret'),
            config('secure-chat.pusher_app_id'),
            ['encrypted' => true]
        );
    }

    public function presenceAuth($channelName, $socketID, $userID, $userInfo)
    {
        return $this->pusher->presenceAuth($channelName, $socketID, $userID, $userInfo);
    }

    public function pushEventToAllChatRoomUsers(ChatRoom $chatRoom, ChatEvent $event): void
    {
        $this->pusher->trigger($this->getChannelListForRoom($chatRoom), 'event', $event->buildPusherPayload());
    }

    public function pushEventToUser(ChatEvent $event, User $user): void
    {
        $this->pusher->trigger($user->chat_channel_name, 'event', $event->buildPusherPayload());
    }

    public function pushMessage(ChatRoom $chatRoom, ChatMessage $message): void
    {
        $this->pusher->trigger($this->getChannelListForRoom($chatRoom), 'message', $message->buildPusherPayload());
    }

    public function socketAuth($channelName, $socketID)
    {
        return $this->pusher->socket_auth($channelName, $socketID);
    }

    protected function getChannelListForRoom(ChatRoom $chatRoom)
    {
        return $chatRoom->users->map(function ($user) {
            return new PrivateChannel($user->chat_channel_name);
        })->toArray();
    }
}
