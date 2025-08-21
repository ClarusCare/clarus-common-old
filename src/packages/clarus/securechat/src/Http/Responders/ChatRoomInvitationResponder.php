<?php

namespace Clarus\SecureChat\Http\Responders;

use Clarus\SecureChat\Http\Transformers\ChatRoomInvitationTransformer;

class ChatRoomInvitationResponder extends BaseResponder
{
    protected $collectionKey = 'invitations';

    protected $itemKey = 'invitation';

    /**
     * @param  mixed  $parentRecord
     * @return ChatRoomInvitationTransformer
     */
    protected function getTransformer($parentRecord = null)
    {
        return new ChatRoomInvitationTransformer();
    }
}
