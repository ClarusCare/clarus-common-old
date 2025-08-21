<?php

namespace Clarus\SecureChat\Http\Transformers;

use League\Fractal\TransformerAbstract;
use Clarus\SecureChat\Models\ChatRoomInvitation;

class ChatRoomInvitationTransformer extends TransformerAbstract
{
    protected $defaultIncludes = [];

    public function transform(ChatRoomInvitation $invitation)
    {
        return [
            'id'                    => (int) $invitation->id,
            'user_id'               => $invitation->user_id,
            'chat_room_id'          => $invitation->chat_room_id,
            'created_by_user_id'    => $invitation->content,
            'created_at'            => $invitation->created_at->toDateTimeString(),
            'updated_at'            => $invitation->updated_at->toDateTimeString(),
        ];
    }
}
