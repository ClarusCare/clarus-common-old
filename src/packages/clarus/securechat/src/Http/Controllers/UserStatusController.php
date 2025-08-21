<?php

namespace Clarus\SecureChat\Http\Controllers;

use Illuminate\Http\Request;
use Clarus\SecureChat\Constants\ChatEventType;
use Clarus\SecureChat\Constants\ChatUserStatus;
use Clarus\SecureChat\Events\ChatEventOccurred;
use Clarus\SecureChat\Gateways\ChatUserProfileGateway;

class UserStatusController extends BaseController
{
    /**
     * @var ChatUserProfileGateway
     */
    private $chatUserProfiles;

    /**
     * UserStatusController constructor.
     *
     * @param  ChatUserProfileGateway  $chatUserProfiles
     */
    public function __construct(ChatUserProfileGateway $chatUserProfiles)
    {
        $this->chatUserProfiles = $chatUserProfiles;
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $errors = $this->validate($request->all(), [
            'status' => 'required',
        ]);

        if ($errors) {
            return $this->failedValidationResponse($errors);
        }

        $status = strtolower($request->get('status'));

        if (! $this->valueInClassConstants($status, ChatUserStatus::class)) {
            return $this->errorResponse(422, 'update_user_status_failure', 'Failed to update user status.', ['status' => ["Status ({$status}) is invalid."]]);
        }

        if ($user->chatUserProfile) {
            if ($user->chatUserProfile->status == $status) {
                return $this->errorResponse(422, 'update_user_status_failure', 'Failed to update user status.', ['status' => ["Status ({$status}) is already set for the user."]]);
            }
        }

        $chatUserProfile = $this->chatUserProfiles->updateByUser($user->id, compact('status'));

        event(new ChatEventOccurred($user->id, ChatEventType::USER_STATUS, ['status' => $chatUserProfile->status]));

        return $this->successResponse('User status updated.');
    }
}
