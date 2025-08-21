<?php

namespace Clarus\SecureChat\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Clarus\SecureChat\Services\PusherService;
use Clarus\SecureChat\Gateways\PartnerGateway;

class AuthController extends BaseController
{
    /**
     * @var PartnerGateway
     */
    protected $partners;

    /**
     * @var PusherService
     */
    protected $pusherService;

    /**
     * @var array
     */
    protected $rules = [
        'channel_name'  => 'required',
        'socket_id'     => 'required',
    ];

    /**
     * AuthController constructor.
     *
     * @param  PusherService  $pusherService
     */
    public function __construct(PusherService $pusherService, PartnerGateway $partners)
    {
        $this->pusherService = $pusherService;
        $this->partners = $partners;
    }

    public function authenticate(Request $request)
    {
        if ($errors = $this->validate($request->all(), $this->rules)) {
            return $this->failedValidationResponse($errors);
        }

        $user = $request->user();
        $channelName = $request->get('channel_name');

        if (strpos($channelName, 'presence') !== false) {
            return $this->authenticatePresenceChannel($channelName, $user, $request->get('socket_id'));
        }

        if ("private-{$user->chat_channel_name}" !== $channelName) {
            return $this->unauthorizedResponse();
        }

        try {
            return $this->pusherService->socketAuth($channelName, $request->get('socket_id'));
        } catch (Exception $e) {
            return $this->errorResponse(403, 'socket_authentication_failure', 'Failed to authenticate access to socket.', ['socket_id' => [$e->getMessage()]]);
        }
    }

    private function authenticatePresenceChannel($channelName, $user, $socketId)
    {
        $userInfo = [
            'id'          => $user->id,
            'chat_status' => $user->chat_status,
        ];

        $partnerId = intval(str_replace('presence-partner_', '', $channelName));
        $partners = $this->partners->getUnifiedPartnersForUser($user);
        $partnerIds = $partners->pluck('id')->toArray();

        if (! in_array($partnerId, $partnerIds)) {
            return $this->unauthorizedResponse();
        }

        try {
            return $this->pusherService->presenceAuth($channelName, $socketId, $user->id, $userInfo);
        } catch (Exception $e) {
            Log::error($e->getMessage());

            return $this->errorResponse(403, 'socket_authentication_failure', 'Failed to authenticate access to socket.', ['socket_id' => [$e->getMessage()]]);
        }
    }
}
