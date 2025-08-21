<?php

namespace Clarus\SecureChat\Gateways;

use Clarus\SecureChat\Models\ChatRoom;

class ChatRoomGateway
{
    /**
     * @var \Clarus\SecureChat\Models\ChatRoom
     */
    private $room;

    public function __construct(ChatRoom $room)
    {
        $this->room = $room ?: $room->newInstance();
    }

    public function find($id)
    {
        return $this->room->findOrFail($id);
    }

    public function findByChannelName($channelName)
    {
        return $this->room->where('channel_name', $channelName)->first();
    }

    public function make()
    {
        return $this->room;
    }

    public function random($excludeId = null)
    {
        $query = $this->room->inRandomOrder();

        if ($excludeId) {
            $query = $query->where('id', '!=', $excludeId);
        }

        return $query->first();
    }

    public function store($input)
    {
        $this->room->fill($input);
        $this->room->save();

        return $this->room;
    }

    public function update($id, $input)
    {
        $room = $this->room->findOrFail($id);
        $room->fill($input);
        $room->save();

        return $room;
    }
}
