<?php

namespace Clarus\SecureChat\Listeners;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\InteractsWithQueue;
use Clarus\SecureChat\Gateways\UserGateway;
use Illuminate\Contracts\Queue\ShouldQueue;
use Clarus\SecureChat\Events\ChatEventSaved;
use Clarus\SecureChat\Constants\ChatEventType;
use Clarus\SecureChat\Events\ChatMessageSaved;
use Clarus\SecureChat\Events\ChatEventOccurred;
use Clarus\SecureChat\Gateways\ChatRoomGateway;
use Clarus\SecureChat\Constants\ChatMessageType;
use Clarus\SecureChat\Gateways\ChatEventGateway;
use Clarus\SecureChat\Gateways\ChatMessageGateway;

class HandleChatEventOccurrence implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * @var ChatEventGateway
     */
    private $chatEvents;

    /**
     * @var ChatMessageGateway
     */
    private $chatMessages;

    /**
     * @var ChatRoomGateway
     */
    private $chatRooms;

    /**
     * @var UserGateway
     */
    private $users;

    /**
     * Create the event listener.
     *
     * @param  ChatEventGateway  $chatEvents
     * @param  ChatRoomGateway  $chatRooms
     * @param  ChatMessageGateway  $chatMessages
     * @param  UserGateway  $users
     */
    public function __construct(
        ChatEventGateway $chatEvents,
        ChatRoomGateway $chatRooms,
        ChatMessageGateway $chatMessages,
        UserGateway $users
    ) {
        $this->chatEvents = $chatEvents;
        $this->chatRooms = $chatRooms;
        $this->chatMessages = $chatMessages;
        $this->users = $users;
    }

    /**
     * Handle the event.
     *
     * @param  ChatEventOccurred  $event
     * @return void
     */
    public function handle(ChatEventOccurred $event): void
    {
        $chatEventData = [
            'user_id'                 => $event->userId,
            'type'                    => $event->type,
            'data'                    => $event->data,
            'chat_room_id'            => $event->data['chat_room_id'] ?? null,
            'chat_room_invitation_id' => $event->data['chat_room_invitation_id'] ?? null,
            'invited_user'            => $event->data['invited_user'] ?? null,
            'status'                  => $event->data['status'] ?? null,
        ];

        $chatMessageData = $this->getMessageDataForChatEvent($chatEventData);

        if ([$chatEvent, $chatMessages, $secondaryChatEvents] = $this->executeChatEventTransaction($chatEventData, $chatMessageData)) {
            event(new ChatEventSaved($chatEvent));
            Log::info("Chat Event {$chatEvent->id} created.");

            foreach ($chatMessages as $chatMessage) {
                // event(new ChatMessageSaved($chatMessage));   // DISABLING THIS FOR NOW; NOT NEEDED BY THE CURRENT CLIENTS
                Log::info("Chat Message {$chatMessage->id} for Chat Event {$chatEvent->id} created.");
            }

            foreach ($secondaryChatEvents as $secondaryChatEvent) {
                event(new ChatEventSaved($secondaryChatEvent));
                Log::info("Chat Event {$secondaryChatEvent->id} created.");
            }
        } else {
            Log::info('Chat Event transaction failed.');
        }
    }

    /**
     * @param $chatEventData
     * @param  null  $chatMessageData
     * @return mixed
     */
    protected function executeChatEventTransaction($chatEventData, $chatMessageData = null)
    {
        return DB::transaction(function () use ($chatEventData, $chatMessageData) {
            $chatEvent = $this->chatEvents->store([
                'user_id' => $chatEventData['user_id'],
                'type'    => $chatEventData['type'],
                'data'    => $chatEventData['data'],
            ]);

            $chatMessages = $secondaryChatEvents = [];

            if ($chatMessageData) {
                foreach ($chatMessageData['chat_room_ids'] as $chatRoomId) {
                    $chatMessage = $this->chatMessages->store([
                        'user_id'      => $chatMessageData['user_id'],
                        'type'         => $chatMessageData['type'],
                        'content'      => $chatMessageData['content'],
                        'chat_room_id' => $chatRoomId,
                    ]);

                    $chatMessages[] = $chatMessage;

                    $secondaryChatEvents[] = $this->chatEvents->store([
                        'user_id' => $chatMessageData['user_id'],
                        'type'    => ChatEventType::MESSAGE,
                        'data'    => [
                            'chat_message_id' => $chatMessage->id,
                            'chat_room_id'    => $chatRoomId,
                        ],
                    ]);
                }
            }

            return [$chatEvent, $chatMessages, $secondaryChatEvents];
        });
    }

    /**
     * @param $data
     * @return string
     */
    protected function getInvitationAcceptMessage($data)
    {
        $user = $this->users->find($data['user_id']);

        return "{$user->full_name} has accepted invitation to room.";
    }

    /**
     * @param $data
     * @return string
     */
    protected function getInvitationCancelMessage($data)
    {
        $invitationId = $data['chat_room_invitation_id'] ?? null;

        if ($invitationId) {
            $user = $this->users->find($data['user_id']);
            $invitedUser = $data['invited_user'] ?: 'user';

            return "{$user->full_name} has cancelled room invitation for {$invitedUser}.";
        }

        return '';
    }

    /**
     * @param $data
     * @return string
     */
    protected function getInvitationDeclineMessage($data)
    {
        $user = $this->users->find($data['user_id']);

        return "{$user->full_name} has declined invitation to room.";
    }

    /**
     * @param $data
     * @return string
     */
    protected function getInvitationSendMessage($data)
    {
        $user = $this->users->find($data['user_id']);

        return "{$user->full_name} has been invited to room.";
    }

    /**
     * @param  array  $chatEventData
     * @return array|null
     */
    protected function getMessageDataForChatEvent($chatEventData)
    {
        $chatRoomIds = [
            $chatEventData['chat_room_id'],
        ];

        switch ($chatEventData['type']) {
            case ChatEventType::ROOM_JOINED:
                $content = $this->getRoomJoinedMessage($chatEventData);
                $type = ChatMessageType::ROOM_JOIN;

                break;
            case ChatEventType::ROOM_LEFT:
                $content = $this->getRoomLeftMessage($chatEventData);
                $type = ChatMessageType::ROOM_LEAVE;

                break;
            case ChatEventType::ROOM_RENAME:
                $content = $this->getRoomRenameMessage($chatEventData);
                $type = ChatMessageType::ROOM_NAME;

                break;
            case ChatEventType::INVITATION_SENT:
                $content = $this->getInvitationSendMessage($chatEventData);
                $type = ChatMessageType::INVITATION_SEND;

                break;
            case ChatEventType::INVITATION_CANCELLED:
                $content = $this->getInvitationCancelMessage($chatEventData);
                $type = ChatMessageType::INVITATION_CANCEL;

                break;
            case ChatEventType::INVITATION_ACCEPTED:
                $content = $this->getInvitationAcceptMessage($chatEventData);
                $type = ChatMessageType::INVITATION_ACCEPT;

                break;
            case ChatEventType::INVITATION_DECLINED:
                $content = $this->getInvitationDeclineMessage($chatEventData);
                $type = ChatMessageType::INVITATION_DECLINE;

                break;
            case ChatEventType::USER_STATUS:
                [$content, $chatRoomIds] = $this->getUserStatusMessageAndChatRooms($chatEventData);
                $type = ChatMessageType::USER_STATUS;

                break;
            default:
                return;
        }

        return [
            'user_id'       => $chatEventData['user_id'],
            'type'          => $type,
            'content'       => $content,
            'chat_room_ids' => $chatRoomIds,
        ];
    }

    /**
     * @param $data
     * @return string
     */
    protected function getRoomJoinedMessage($data)
    {
        $user = $this->users->find($data['user_id']);

        return "{$user->full_name} joined the room.";
    }

    /**
     * @param $data
     * @return string
     */
    protected function getRoomLeftMessage($data)
    {
        $user = $this->users->find($data['user_id']);

        return "{$user->full_name} left the room.";
    }

    /**
     * @param $data
     * @return string
     */
    protected function getRoomRenameMessage($data)
    {
        $chatRoomId = $data['chat_room_id'] ?? null;

        if ($chatRoomId) {
            $chatRoom = $this->chatRooms->find($chatRoomId);
            $user = $this->users->find($data['user_id']);

            return "{$user->full_name} changed the room name to '{$chatRoom->name}'.";
        }

        return '';
    }

    /**
     * @param $data
     * @return array
     */
    protected function getUserStatusMessageAndChatRooms($data)
    {
        $user = $this->users->find($data['user_id']);
        $message = "{$user->full_name} changed status to ".ucwords($data['status']).'.';

        return [$message, $user->chatRooms->pluck('id')->toArray()];
    }
}
