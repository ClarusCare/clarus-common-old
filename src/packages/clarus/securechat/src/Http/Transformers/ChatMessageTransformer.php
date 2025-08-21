<?php

namespace Clarus\SecureChat\Http\Transformers;

use League\Fractal\TransformerAbstract;
use Clarus\SecureChat\Models\ChatMessage;

class ChatMessageTransformer extends TransformerAbstract
{
    protected $defaultIncludes = [];

    public function transform(ChatMessage $chatMessage)
    {
        return [
            'id'            => (int) $chatMessage->id,
            'user_id'       => $chatMessage->user_id,
            'chat_room_id'  => $chatMessage->chat_room_id,
            'content'       => $chatMessage->content,
            'type'          => $chatMessage->type,
            'from'          => $chatMessage->from,
            'created_at'    => $chatMessage->created_at->toDateTimeString(),
            'updated_at'    => $chatMessage->updated_at->toDateTimeString(),
            'chat_room'     => [
                'id'    => (int) $chatMessage->chat_room_id,
                'type'  => 'chat_room',
            ],
            'chat_user'     => [
                'id'    => (int) $chatMessage->user_id,
                'type'  => 'chat_user',
            ],
        ];
    }
}
