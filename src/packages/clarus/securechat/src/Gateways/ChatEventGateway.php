<?php

namespace Clarus\SecureChat\Gateways;

use Clarus\SecureChat\Models\ChatEvent;

class ChatEventGateway
{
    /**
     * @var \Clarus\SecureChat\Models\ChatEvent
     */
    private $event;

    /**
     * ChatEventGateway constructor.
     *
     * @param  ChatEvent  $event
     */
    public function __construct(ChatEvent $event)
    {
        $this->event = $event ?: $event->newInstance();
    }

    public function find($id)
    {
        return $this->event->findOrFail($id);
    }

    public function make()
    {
        return $this->event;
    }

    /**
     * @param $input
     * @return ChatEvent
     */
    public function store($input)
    {
        $event = $this->event->newInstance();
        $event->fill($input);
        $event->save();

        return $event;
    }
}
