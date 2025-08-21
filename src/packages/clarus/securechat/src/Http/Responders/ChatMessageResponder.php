<?php

namespace Clarus\SecureChat\Http\Responders;

use Clarus\SecureChat\Http\Transformers\ChatMessageTransformer;

class ChatMessageResponder extends BaseResponder
{
    protected $collectionKey = 'chat_messages';

    protected $itemKey = 'chat_message';

    /**
     * @param  mixed  $parentRecord
     * @return ChatMessageTransformer
     */
    protected function getTransformer($parentRecord = null)
    {
        return new ChatMessageTransformer();
    }
}
