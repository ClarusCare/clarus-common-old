<?php

namespace Clarus\SecureChat\Http\Transformers;

use Clarus\SecureChat\Models\ChatRoom;
use League\Fractal\TransformerAbstract;

class ChatRoomTransformer extends TransformerAbstract
{
    protected $defaultIncludes = ['users'];

    public function includeUsers(ChatRoom $chatRoom)
    {
        $users = $chatRoom->users;

        if ($users) {
            return $this->item($users, function ($users) use ($chatRoom) {
                $data = [];
                foreach ($users as $user) {
                    $userRoom = $user->chatRooms->find($chatRoom->id);
                    $userRoomActive = $userRoom->pivot->active;

                    $data[] = [
                        'id'                    => $user->id,
                        'first_name'            => $user->first_name,
                        'last_name'             => $user->last_name,
                        'chat_status'           => $user->chat_status,
                        'active'                => $userRoomActive,
                        'profile_image'         => $user->profile_image,
                        'profile_image_retina'  => $user->profile_image_retina,
                        'created_at'            => $user->created_at->toDateTimeString(),
                        'updated_at'            => $user->updated_at->toDateTimeString(),
                    ];
                }

                return $data;
            });
        }
    }

    public function transform(ChatRoom $chatRoom)
    {
        return [
            'id'                    => (int) $chatRoom->id,
            'name'                  => $chatRoom->name,
            'partner_id'            => $chatRoom->partner_id,
            'private'               => $chatRoom->private,
            'user_id'               => $chatRoom->user_id,
            'latest_message'        => $chatRoom->latest_message,
            'unread_message_count'  => $chatRoom->unread_message_count,
            'created_at'            => $chatRoom->created_at->toDateTimeString(),
            'updated_at'            => $chatRoom->updated_at->toDateTimeString(),
        ];
    }
}
