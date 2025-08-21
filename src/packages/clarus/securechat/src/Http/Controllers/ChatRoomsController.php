<?php

namespace Clarus\SecureChat\Http\Controllers;

use Illuminate\Http\Request;
use Clarus\SecureChat\Gateways\UserGateway;
use Clarus\SecureChat\Constants\ChatEventType;
use Clarus\SecureChat\Gateways\PartnerGateway;
use Clarus\SecureChat\Events\ChatEventOccurred;
use Clarus\SecureChat\Gateways\ChatRoomGateway;
use Clarus\SecureChat\Http\Responders\ChatRoomResponder;

class ChatRoomsController extends BaseController
{
    /**
     * @var ChatRoomGateway
     */
    protected $chatRooms;

    /**
     * @var PartnerGateway
     */
    protected $partners;

    /**
     * @var UserGateway
     */
    protected $users;

    /**
     * @var ChatRoomResponder
     */
    private $responder;

    /**
     * ChatRoomsController constructor.
     *
     * @param  ChatRoomGateway  $chatRooms
     * @param  PartnerGateway  $partners
     * @param  UserGateway  $users
     * @param  ChatRoomResponder  $responder
     */
    public function __construct(
        ChatRoomGateway $chatRooms,
        PartnerGateway $partners,
        UserGateway $users,
        ChatRoomResponder $responder
    ) {
        $this->chatRooms = $chatRooms;
        $this->partners = $partners;
        $this->users = $users;
        $this->responder = $responder;
    }

    public function index(Request $request)
    {
        $user = $request->user();

        if ($request->has('partner_id')) {
            $chatRooms = $user->chatRooms()
                ->where('partner_id', $request->get('partner_id'))
                ->with('users')
                ->get();
        } else {
            $chatRooms = $user->chatRooms()
                ->with('users')
                ->get();
        }

        return $this->responder->createCollectionResponse($chatRooms);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $request->merge(['user_id' => $user->id]);

        $errors = $this->validate($request->all(), [
            'name'       => 'required',
            'user_id'    => 'required',
            'partner_id' => 'required',
        ]);

        if ($errors) {
            return $this->failedValidationResponse($errors);
        }

        // Make sure user has access to this partner
        $partner = $this->partners->find($request->get('partner_id'));

        if (! $partner || $partner->active == false) {
            return $this->errorResponse(403, 'no_partner_access', 'You do not have access to this partner.');
        }

        if (! $this->partners->userHasAccess($user, $partner)) {
            return $this->errorResponse(403, 'no_partner_access', 'You do not have access to this partner.');
        }

        $chatRoom = $this->chatRooms->store($request->all());

        $chatRoom->addUser($user);

        // Immediately add users if they are included and have access
        if ($request->has('invited_user_ids')) {
            $rawInvitedUserIds = $request->get('invited_user_ids', '');
            $invitedUserIds = explode(',', $rawInvitedUserIds);
            foreach ($invitedUserIds as $invitedUserId) {
                if ($userToInvite = $this->users->find($invitedUserId)) {
                    if ($this->partners->userHasAccess($userToInvite, $partner)) {
                        $chatRoom->addUser($userToInvite);
                    }
                }
            }
        }

        return $this->responder->createItemResponse($chatRoom);
    }

    public function update(Request $request, $id)
    {
        $user = $request->user();

        if (! $this->userBelongsToRoom($user, $id)) {
            return $this->unauthorizedResponse();
        }

        $input = $this->parseJsonApiInput($request->all(), 'chatRoom');

        $errors = $this->validate($input, [
            'name' => 'required',
        ]);

        if ($errors) {
            return $this->failedValidationResponse($errors);
        }

        $chatRoom = $this->chatRooms->update($id, $input);

        event(new ChatEventOccurred($user->id, ChatEventType::ROOM_RENAME, ['chat_room_id' => $id]));

        return $this->responder->createItemResponse($chatRoom);
    }
}
