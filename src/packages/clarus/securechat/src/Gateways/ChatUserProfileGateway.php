<?php

namespace Clarus\SecureChat\Gateways;

use Clarus\SecureChat\Models\ChatUserProfile;
use Clarus\SecureChat\Constants\ChatUserStatus;

class ChatUserProfileGateway
{
    /**
     * @var \Clarus\SecureChat\Models\ChatUserProfile
     */
    private $profile;

    /**
     * @var UserGateway
     */
    private $userGateway;

    /**
     * ChatUserProfileGateway constructor.
     *
     * @param  ChatUserProfile  $profile
     * @param  UserGateway  $userGateway
     */
    public function __construct(ChatUserProfile $profile, UserGateway $userGateway)
    {
        $this->profile = $profile ?: $profile->newInstance();
        $this->userGateway = $userGateway;
    }

    public function find($id)
    {
        return $this->profile->findOrFail($id);
    }

    public function make()
    {
        return $this->profile;
    }

    /**
     * @param $input
     * @return ChatUserProfile
     */
    public function store($input)
    {
        $this->profile->fill($input);
        $this->profile->save();

        return $this->profile;
    }

    /**
     * @param $id
     * @param $input
     * @return ChatUserProfile
     */
    public function update($id, $input)
    {
        $profile = $this->profile->findOrFail($id);
        $profile->fill($input);
        $profile->save();

        return $profile;
    }

    /**
     * @param $userId
     * @param $input
     * @return ChatUserProfile
     */
    public function updateByUser($userId, $input)
    {
        $user = $this->userGateway->find($userId);

        if (! $user->chatUserProfile) {
            $amendedInput = [
                'user_id' => $userId,
                'status'  => $input['status'] ?? ChatUserStatus::ACTIVE,
                'data'    => $input['data'] ?? [],
            ];

            $profile = $this->store($amendedInput);
        } else {
            $profile = $this->update($user->chatUserProfile->id, $input);
        }

        return $profile;
    }
}
