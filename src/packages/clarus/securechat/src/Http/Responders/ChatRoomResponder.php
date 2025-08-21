<?php

namespace Clarus\SecureChat\Http\Responders;

use Clarus\SecureChat\Http\Transformers\ChatRoomTransformer;

class ChatRoomResponder extends BaseResponder
{
    protected $collectionKey = 'chat_rooms';

    protected $itemKey = 'chat_room';

    /**
     * @param  mixed  $parentRecord
     * @return ChatRoomTransformer ;
     */
    protected function getTransformer($parentRecord = null)
    {
        return new ChatRoomTransformer();
    }
}
