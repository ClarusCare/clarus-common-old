<?php

namespace Clarus\SecureChat\Gateways;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class UserGateway extends BaseGateway
{
    /**
     * @var \Clarus\SecureChat\Gateways\PartnerGateway
     */
    private $partners;

    /**
     * @var \App\Models\User
     */
    private $user;

    public function __construct(User $user, PartnerGateway $partners)
    {
        $this->user = $user ?: $user->newInstance();
        $this->partners = $partners;
    }

    /**
     * @param  User  $user
     * @param  string  $filterPartnerId
     * @return Illuminate\Support\Collection
     */
    public function buildChatUserList(User $user, $filterPartnerId = null)
    {
        $users = collect();

        $partners = $this->partners->getUnifiedPartnersForUser($user);

        if ($filterPartnerId) {
            $partners = $partners->reject(function ($partner) use ($filterPartnerId) {
                return $partner->id != $filterPartnerId;
            });
        }

        $partners->each(function ($partner) use (&$users): void {
            $partnerUsers = $this->partners->buildFlatUserList($partner);
            $users = $users->merge($partnerUsers);
        });

        $users = $this->uniqueByKey($users, 'id');

        return $users->values();
    }

    public function find($id)
    {
        return $this->user->find($id);
    }

    /**
     * @param  User  $user
     * @param $messageIDs
     */
    public function markMessagesAsRead(User $user, $messageIDs): void
    {
        $data = [];

        foreach ($messageIDs as $messageID) {
            $data[$messageID] = ['read_at' => date('Y-m-d H:i:s')];
        }

        $user->chatMessages()->syncWithoutDetaching($data);
    }

    /**
     * Mark all unread messages 'read' as of the current time in a particular
     * chat room.
     *
     * @param  User  $user
     * @param $chatRoomId
     */
    public function markMessagesAsReadByChatRoom(User $user, $chatRoomId): void
    {
        $messages = DB::table('chat_messages')
            ->where('chat_room_id', $chatRoomId)
            ->whereNotIn('id', function ($q) use ($user): void {
                $q->select('chat_message_id')
                    ->from('chat_message_user')
                    ->where('user_id', $user->id)
                    ->whereNotNull('read_at');
            })
            ->get(['id']);

        $data = [];

        foreach ($messages as $message) {
            $data[$message->id] = ['read_at' => date('Y-m-d H:i:s')];
        }

        $user->chatMessages()->syncWithoutDetaching($data);
    }

    public function random($excludeId = null)
    {
        $query = $this->user->inRandomOrder();

        if ($excludeId) {
            $query = $query->where('id', '!=', $excludeId);
        }

        return $query->first();
    }

    /**
     * Return count of unread chat messages.
     *
     * @param  User  $user
     * @return int
     */
    public function unreadChatMessageCount(User $user)
    {
        return DB::table('chat_messages')
            ->whereIn('chat_room_id', function ($q) use ($user): void {
                $q->select('chat_room_id')
                    ->from('chat_room_user')
                    ->where('user_id', $user->id)
                    ->where('active', true);
            })
            ->whereNotIn('id', function ($q) use ($user): void {
                $q->select('chat_message_id')
                    ->from('chat_message_user')
                    ->where('user_id', $user->id);
            })
            ->count();
    }
}
