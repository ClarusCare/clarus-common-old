<?php

namespace Clarus\SecureChat\Events;

use Illuminate\Support\Facades\Log;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Clarus\SecureChat\Models\ChatEvent;
use Clarus\SecureChat\Gateways\UserGateway;
use Illuminate\Broadcasting\PrivateChannel;
use Clarus\SecureChat\Constants\ChatEventType;
use Clarus\SecureChat\Gateways\PartnerGateway;
use Clarus\SecureChat\Constants\ChatEventScope;
use Clarus\SecureChat\Gateways\ChatRoomGateway;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class ChatEventSaved implements ShouldBroadcast
{
    use InteractsWithSockets, SerializesModels;

    /**
     * The name of the queue on which to place the event.
     *
     * @var string
     */
    public $broadcastQueue;

    /**
     * @var ChatEvent
     */
    public $chatEvent;

    /**
     * ChatEventSaved constructor.
     *
     * @param  ChatEvent  $chatEvent
     */
    public function __construct(ChatEvent $chatEvent)
    {
        $this->chatEvent = $chatEvent;
        $this->broadcastQueue = env('SQS_SECURE_CHAT_QUEUE_URL', 'secure-chat');
    }

    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs()
    {
        return 'chat_event';
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        $channels = $this->getChannelList($this->chatEvent) ?: new PrivateChannel('overflow_channel');

        $channelLimit = config('secure-chat.pusher_channel_limit');

        if (is_array($channels) && count($channels) > $channelLimit) {
            Log::warning("ChatEvent {$this->chatEvent->id} channel list exceeds Pusher limit ({$channelLimit}); concatenated for broadcast.");
            $channels = array_slice($channels, 0, 100);
        }

        return $channels;
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith()
    {
        return $this->chatEvent->buildPusherPayload();
    }

    /**
     * @return mixed
     */
    protected function getChannelList()
    {
        switch ($this->chatEvent->broadcast_scope) {
            case ChatEventScope::USER:
                return $this->chatEvent->user ? new PrivateChannel($this->chatEvent->user->chat_channel_name) : null;
            case ChatEventScope::CHAT_ROOM:
                if ($this->chatEvent->chat_room_id) {
                    $channels = $this->getChannelListForChatRoom($this->chatEvent->chat_room_id);

                    if ($this->chatEvent->type == ChatEventType::ROOM_LEFT) {
                        // We also need to send this to the user who just left, so that their clients can update
                        array_push($channels, new PrivateChannel($this->chatEvent->user->chat_channel_name));
                    }

                    return $channels;
                }

                return;
            case ChatEventScope::USER_PARTNERS:
//                DISABLE BROADCAST TO ALL USER PARTNERS; Not necessary and overloads PUSHER
//                return $this->getChannelListForUserPartners($this->chatEvent->user);
            case ChatEventScope::PARTNER:
                return $this->getChannelListForPartner($this->chatEvent->partner_id);
            default:
                return;
        }
    }

    /**
     * @param $chatRoomId
     * @return array
     */
    protected function getChannelListForChatRoom($chatRoomId)
    {
        $chatRoomGateway = app(ChatRoomGateway::class);
        $chatRoom = $chatRoomGateway->find($chatRoomId);

        return $chatRoom->users->map(function ($user) {
            return new PrivateChannel($user->chat_channel_name);
        })->toArray();
    }

    /**
     * @param  int  $partnerId
     * @return array
     */
    protected function getChannelListForPartner($partnerId)
    {
        $partnerGateway = app(PartnerGateway::class);
        if ($partner = $partnerGateway->find($partnerId)) {
            $users = $partnerGateway->buildFlatUserList($partner);

            return $users->map(function ($user) {
                return new PrivateChannel($user->chat_channel_name);
            })->toArray();
        }
    }

    /**
     * @param $user
     * @return array
     */
    protected function getChannelListForUserPartners($user)
    {
        $userGateway = app(UserGateway::class);
        $users = $userGateway->buildChatUserList($user);

        return $users->map(function ($user) {
            return new PrivateChannel($user->chat_channel_name);
        })->toArray();
    }
}
