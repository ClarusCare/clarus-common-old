<?php

namespace Clarus\SecureChat\Http\Controllers;

use Illuminate\Http\Request;
use Clarus\SecureChat\Gateways\UserGateway;
use Clarus\SecureChat\Constants\ChatEventType;
use Clarus\SecureChat\Events\ChatEventOccurred;
use Clarus\SecureChat\Gateways\ChatRoomGateway;
use Clarus\SecureChat\Http\Responders\UserResponder;

class ChatRoomUsersController extends BaseController
{
    /**
     * @var ChatRoomGateway
     */
    protected $chatRooms;

    /**
     * @var UserResponder
     */
    protected $responder;

    /**
     * @var UserGateway
     */
    protected $users;

    /**
     * ChatRoomUsersController constructor.
     *
     * @param  UserGateway  $users
     * @param  ChatRoomGateway  $chatRooms
     * @param  UserResponder  $responder
     */
    public function __construct(UserGateway $users, ChatRoomGateway $chatRooms, UserResponder $responder)
    {
        $this->users = $users;
        $this->chatRooms = $chatRooms;
        $this->responder = $responder;
    }

    public function delete(Request $request, $chatRoomId, $userId)
    {
        $user = $request->user();

        if ($user->id != $userId) {
            return $this->unauthorizedResponse();
        }

        $chatRoom = $this->chatRooms->find($chatRoomId);

        if (! $chatRoom->userInRoom($user)) {
            return $this->errorResponse(400, 'remove_user_failure', 'Unable to remove user from chat room.');
        }

        if ($r = $chatRoom->removeUser($user)) {
            event(new ChatEventOccurred($user->id, ChatEventType::ROOM_LEFT, ['chat_room_id' => $chatRoom->id]));

            return $this->deleteResponse('User removed from chat room.');
        }

        return $this->errorResponse(400, 'remove_user_failure', 'Unable to remove user from chat room.');
    }

    public function index(Request $request, $chatRoomId)
    {
        $user = $request->user();
        $chatRoom = $this->chatRooms->find($chatRoomId);

        if (! $chatRoom->userInRoom($user)) {
            return $this->unauthorizedResponse();
        }

        return $this->responder->createCollectionResponse($chatRoom->users, $chatRoom);
    }
}
