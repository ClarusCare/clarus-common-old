<?php

namespace Clarus\SecureChat\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Clarus\SecureChat\Gateways\UserGateway;
use Clarus\SecureChat\Constants\ChatEventType;
use Clarus\SecureChat\Events\ChatEventOccurred;
use Clarus\SecureChat\Gateways\ChatRoomGateway;
use Clarus\SecureChat\Constants\ChatMessageType;
use Clarus\SecureChat\Gateways\ChatMessageGateway;
use Clarus\SecureChat\Http\Responders\ChatMessageResponder;
use Clarus\SecureChat\Jobs\SendNewMessagePushNotifications;

class ChatRoomChatMessagesController extends BaseController
{
    /**
     * @var ChatRoomGateway
     */
    protected $chatRooms;

    /**
     * @var ChatMessageGateway
     */
    protected $messages;

    /**
     * @var UserGateway
     */
    protected $users;

    /**
     * @var ChatMessageResponder
     */
    private $responder;

    /**
     * ChatRoomChatMessagesController constructor.
     *
     * @param  ChatMessageGateway  $messages
     * @param  ChatRoomGateway  $chatRooms
     * @param  UserGateway  $users
     * @param  ChatMessageResponder  $responder
     */
    public function __construct(
        ChatMessageGateway $messages,
        ChatRoomGateway $chatRooms,
        UserGateway $users,
        ChatMessageResponder $responder
    ) {
        $this->messages = $messages;
        $this->chatRooms = $chatRooms;
        $this->users = $users;
        $this->responder = $responder;
    }

    public function index(Request $request, $chatRoomID)
    {
        $user = $request->user();

        if (! $this->userBelongsToRoom($user, $chatRoomID)) {
            return $this->unauthorizedResponse();
        }

        $perPage = (int) $request->get('per_page', 25);
        $latestMessageId = $request->get('latest_message_id');
        $direction = $request->get('direction');

        [$messages, $messageCount, $remainingCount] = $this->messages->paginateByChatRoom($chatRoomID, $perPage, $direction, $latestMessageId);

        foreach ($messages as $key => $message) {
            $message['chat_room'] = [
                'id'   => (int) $chatRoomID,
                'type' => 'chat_room',
            ];
            $message['chat_user'] = [
                'id'   => (int) $message->user_id,
                'type' => 'chat_user',
            ];
            $messages[$key] = $message;
        }

        return response([
            'count'     => $messageCount,
            'remaining' => $remainingCount,
            'messages'  => $messages->toArray(),
        ]);
    }

    public function markAsRead(Request $request, $chatRoomID)
    {
        $user = $request->user();

        if (! $this->userBelongsToRoom($user, $chatRoomID)) {
            return $this->unauthorizedResponse();
        }

        try {
            $this->users->markMessagesAsReadByChatRoom($user, $chatRoomID);

            return $this->responder->successResponse();
        } catch (Exception $e) {
            Log::info($e->getMessage());

            return $this->errorResponse(400, 'mark_as_read_failure', 'Failed to mark messages read.', ['mark_as_read' => [$e->getMessage()]]);
        }
    }

    public function store(Request $request, $chatRoomID)
    {
        $user = $request->user();

        if (! $this->userBelongsToRoom($user, $chatRoomID)) {
            return $this->unauthorizedResponse();
        }

        $input = array_merge($request->all(), [
            'user_id'      => $user->id,
            'chat_room_id' => $chatRoomID,
            'type'         => ChatMessageType::NEW_MESSAGE,
        ]);

        $message = $this->messages->store($input);

        event(new ChatEventOccurred($user->id, ChatEventType::MESSAGE, [
            'chat_message_id' => $message->id,
            'chat_room_id'    => $chatRoomID,
        ]));

        dispatch(new SendNewMessagePushNotifications($chatRoomID, $message));

        return $this->responder->createItemResponse($message);
    }
}
