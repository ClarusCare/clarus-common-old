<?php

namespace Clarus\SecureChat\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Clarus\SecureChat\Gateways\UserGateway;

class UserChatMessagesController extends BaseController
{
    /**
     * @var UserGateway
     */
    protected $users;

    /**
     * UserChatMessagesController constructor.
     *
     * @param  UserGateway  $users
     */
    public function __construct(UserGateway $users)
    {
        $this->users = $users;
    }

    public function markAsRead(Request $request)
    {
        $user = $request->user();

        $errors = $this->validate($request->all(), [
            'message_ids' => 'required',
        ]);

        if ($errors) {
            return $this->failedValidationResponse($errors);
        }

        try {
            $this->users->markMessagesAsRead($user, $request->get('message_ids'));

            return $this->successResponse();
        } catch (Exception $e) {
            Log::info($e->getMessage());

            return $this->errorResponse(400, 'mark_as_read_failure', 'Failed to mark messages read.', ['mark_as_read' => [$e->getMessage()]]);
        }
    }
}
