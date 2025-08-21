<?php

namespace Clarus\SecureChat\Gateways;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Clarus\SecureChat\Models\ChatMessage;

class ChatMessageGateway
{
    /**
     * @var \Clarus\SecureChat\Models\ChatMessage
     */
    private $message;

    /**
     * ChatMessageGateway constructor.
     *
     * @param  ChatMessage  $message
     */
    public function __construct(ChatMessage $message)
    {
        $this->message = $message ?: $message->newInstance();
    }

    public function find($id)
    {
        return $this->message->findOrFail($id);
    }

    public function make()
    {
        return $this->message;
    }

    /**
     * @param  int  $chatRoomID
     * @param  int  $perPage
     * @param  string  $direction
     * @param  int  $latestMessageId
     * @return mixed
     */
    public function paginateByChatRoom($chatRoomID, $perPage = 25, $direction = 'prev', $latestMessageId = null)
    {
        $query = $this->message
            ->where('chat_room_id', $chatRoomID)
            ->orderBy('id', ($direction == 'next' ? 'asc' : 'desc'));

        $comparator = ($direction == 'next' ? '>' : '<');

        if ($latestMessageId) {
            $query = $query->where('id', $comparator, $latestMessageId);
        }

        $messages = $query->take($perPage)->get()->sortBy('id')->values();

        $lastId = ($direction == 'next' ? $messages->max('id') : $messages->min('id'));

        $remaining = 0;

        if ($lastId) {
            $remaining = DB::table('chat_messages')
                ->where('chat_room_id', $chatRoomID)
                ->where('id', $comparator, $lastId)
                ->count();
        }

        return [$messages, $messages->count(), $remaining];
    }

    /**
     * @param $input
     * @return ChatMessage
     */
    public function store($input)
    {
        $message = $this->message->newInstance();
        $message->fill($input);
        $message->save();

        $message->syncForRoomUsers();

        return $message;
    }

    /**
     * @param  Collection  $messages
     * @param $user
     * @param  bool  $markAsRead
     * @return mixed
     */
    public function syncCollectionForUser(Collection $messages, $user, $markAsRead = false)
    {
        $data = [];

        foreach ($messages as $message) {
            $data[$message->id] = ['read_at' => $markAsRead ? date('Y-m-d H:i:s') : null];
        }

        return $user->chatMessages()->syncWithoutDetaching($data);
    }
}
