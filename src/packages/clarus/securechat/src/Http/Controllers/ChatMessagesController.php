<?php

namespace Clarus\SecureChat\Http\Controllers;

use Illuminate\Http\Request;
use Clarus\SecureChat\Gateways\ChatMessageGateway;
use Clarus\SecureChat\Http\Responders\ChatMessageResponder;

class ChatMessagesController extends BaseController
{
    /**
     * @var ChatMessageGateway
     */
    protected $messages;

    /**
     * @var ChatMessageResponder
     */
    private $responder;

    /**
     * ChatMessagesController constructor.
     *
     * @param  ChatMessageGateway  $messages
     * @param  ChatMessageResponder  $responder
     */
    public function __construct(ChatMessageGateway $messages, ChatMessageResponder $responder)
    {
        $this->messages = $messages;
        $this->responder = $responder;
    }

    public function show(Request $request, $id)
    {
        $message = $this->messages->find($id);

        if ($this->userBelongsToRoom($request->user(), $message->chat_room_id)) {
            return $this->responder->createItemResponse($message);
        }

        return $this->unauthorizedResponse();
    }
}
