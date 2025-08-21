<?php

namespace Clarus\SecureChat\Http\Controllers;

use Illuminate\Http\Request;
use Clarus\SecureChat\Gateways\UserGateway;
use Clarus\SecureChat\Constants\ChatEventType;
use Clarus\SecureChat\Events\ChatEventOccurred;
use Clarus\SecureChat\Gateways\ChatRoomGateway;
use Clarus\SecureChat\Gateways\ChatRoomInvitationGateway;
use Clarus\SecureChat\Http\Responders\ChatRoomInvitationResponder;

class ChatRoomInvitationsController extends BaseController
{
    /**
     * @var ChatRoomGateway
     */
    protected $chatRooms;

    /**
     * @var ChatRoomInvitationGateway
     */
    protected $invitations;

    /**
     * @var UserGateway
     */
    protected $users;

    /**
     * @var ChatRoomInvitationResponder
     */
    private $responder;

    /**
     * ChatRoomInvitationsController constructor.
     *
     * @param  ChatRoomInvitationGateway  $invitations
     * @param  UserGateway  $users
     * @param  ChatRoomGateway  $chatRooms
     * @param  ChatRoomInvitationResponder  $responder
     */
    public function __construct(
        ChatRoomInvitationGateway $invitations,
        UserGateway $users,
        ChatRoomGateway $chatRooms,
        ChatRoomInvitationResponder $responder
    ) {
        $this->invitations = $invitations;
        $this->users = $users;
        $this->chatRooms = $chatRooms;
        $this->responder = $responder;
    }

    public function accept(Request $request, $id)
    {
        $user = $request->user();
        $invitation = $this->invitations->find($id);

        if ($user->id !== $invitation->user_id) {
            return $this->unauthorizedResponse();
        }

        if ($invitation->chatRoom->addUser($user)) {
            $this->invitations->destroy($id);

            event(new ChatEventOccurred($user->id, ChatEventType::INVITATION_ACCEPTED, ['chat_room_invitation_id' => $invitation->id, 'chat_room_id' => $invitation->chat_room_id]));

            return $this->successResponse('Chat room joined.');
        }

        return $this->errorResponse(400, 'accept_invitation_failure', 'Failed to accept invitation.');
    }

    public function delete(Request $request, $id)
    {
        $user = $request->user();
        $invitation = $this->invitations->find($id);

        if ($user->id !== $invitation->user_id && $user->id !== $invitation->created_by_user_id) {
            return $this->unauthorizedResponse();
        }

        $this->invitations->destroy($id);

        if ($user->id == $invitation->user_id) {
            event(new ChatEventOccurred($user->id, ChatEventType::INVITATION_DECLINED, ['chat_room_invitation_id' => $invitation->id, 'chat_room_id' => $invitation->chat_room_id, 'invited_user' => $invitation->user->full_name]));
        } elseif ($user->id == $invitation->created_by_user_id) {
            event(new ChatEventOccurred($user->id, ChatEventType::INVITATION_CANCELLED, ['chat_room_invitation_id' => $invitation->id, 'chat_room_id' => $invitation->chat_room_id, 'invited_user' => $invitation->user->full_name]));
        }

        return $this->deleteResponse('Invitation deleted.');
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $invitations = $user->chatRoomInvitations;

        return $this->responder->createCollectionResponse($invitations);
    }

    public function store(Request $request, $chatRoomID)
    {
        $user = $request->user();

        if (! $this->userBelongsToRoom($user, $chatRoomID)) {
            return $this->unauthorizedResponse();
        }

        $invitedUserID = $request->get('user_id');

        if ($user->id === $invitedUserID) {
            return $this->errorResponse(400, 'store_invitation_failure', 'Failed to create invitation.', ['user_id' => 'User cannot invite self.']);
        }

        if (! $invitedUser = $this->users->find($invitedUserID)) {
            return $this->errorResponse(400, 'store_invitation_failure', 'Failed to create invitation.', ['user_id' => 'User not found.']);
        }

        if ($this->userBelongsToRoom($invitedUser, $chatRoomID)) {
            return $this->errorResponse(400, 'store_invitation_failure', 'Failed to create invitation.', ['user_id' => 'Invited user is already in the requested chat room.']);
        }

        if (! $this->usersBelongToSamePartner($user, $invitedUser)) {
            return $this->errorResponse(400, 'store_invitation_failure', 'Failed to create invitation.', ['user_id' => 'Invited user is not authorized.']);
        }

        $invitation = $this->invitations->store([
            'user_id'            => $invitedUserID,
            'chat_room_id'       => $chatRoomID,
            'created_by_user_id' => $user->id,
        ]);

        event(new ChatEventOccurred($invitedUserID, ChatEventType::INVITATION_SENT, ['chat_room_invitation_id' => $invitation->id, 'chat_room_id' => $invitation->chat_room_id]));

        return $this->responder->createItemResponse($invitation);
    }
}
