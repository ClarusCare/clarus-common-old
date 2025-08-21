<?php

namespace Clarus\SecureChat\Gateways;

use Clarus\SecureChat\Models\ChatRoomInvitation;

class ChatRoomInvitationGateway
{
    /**
     * @var \Clarus\SecureChat\Models\ChatRoomInvitation
     */
    private $invitation;

    public function __construct(ChatRoomInvitation $invitation)
    {
        $this->invitation = $invitation ?: $invitation->newInstance();
    }

    public function destroy($id)
    {
        $invitation = $this->invitation->findOrFail($id);

        return $invitation->delete();
    }

    public function find($id)
    {
        return $this->invitation->findOrFail($id);
    }

    public function make()
    {
        return $this->invitation;
    }

    public function store($input)
    {
        $this->invitation->fill($input);
        $this->invitation->save();

        return $this->invitation;
    }
}
