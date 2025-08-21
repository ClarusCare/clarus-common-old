<?php

namespace Clarus\SecureChat\Traits;

use App\Models\Partner;
use Illuminate\Support\Facades\DB;
use Clarus\SecureChat\Models\ChatRoom;
use Clarus\SecureChat\Models\ChatEvent;
use Clarus\SecureChat\Models\ChatMessage;
use Clarus\SecureChat\Models\ChatUserProfile;
use Clarus\SecureChat\Constants\ChatUserStatus;
use Clarus\SecureChat\Constants\ChatMessageType;
use Clarus\SecureChat\Models\ChatRoomInvitation;

trait SecureChatUser
{
    public function chatEvents()
    {
        return $this->hasMany(ChatEvent::class);
    }

    public function chatMessages()
    {
        return $this->belongsToMany(ChatMessage::class, 'chat_message_user')->withPivot('read_at');
    }

    public function chatRoomInvitations()
    {
        return $this->hasMany(ChatRoomInvitation::class);
    }

    public function chatRooms()
    {
        return $this->belongsToMany(ChatRoom::class, 'chat_room_user')->withPivot('active');
    }

    public function chatUserProfile()
    {
        return $this->hasOne(ChatUserProfile::class);
    }

    public function getChatChannelNameAttribute()
    {
        $channelName = $this->attributes['chat_channel_name'];
        if ($channelName && $channelName != '') {
            return $channelName;
        }

        return 'user_'.$this->id.'_channel';
    }

    public function getChatStatusAttribute()
    {
        return ChatUserStatus::AWAY;
    }

    /**
     * Returns boolean indicating user has a link to a partner through direct affiliation.
     *
     * @param $partnerId
     * @return bool
     */
    public function isAttachedToPartner($partnerId)
    {
        if (in_array($partnerId, $this->partners->pluck('id')->toArray())) {
            return true;
        }

        $activePartnerProvidersCount = DB::table('partner_providers')
            ->whereIn('provider_id', $this->providers->pluck('id')->toArray())
            ->where('partner_id', $partnerId)
            ->where('active', true)
            ->count();

        if ($activePartnerProvidersCount) {
            return true;
        }

        return false;
    }

    public function ownedChatRooms()
    {
        return $this->hasMany(ChatRoom::class, 'user_id');
    }

    public function setInactiveInPartnerChatRooms($partnerId)
    {
        $partnerChatRoomIds = $this->chatRooms->where('partner_id', $partnerId)->pluck('id')->toArray();

        foreach ($partnerChatRoomIds as $chatRoomId) {
            $this->chatRooms()->updateExistingPivot($chatRoomId, ['active' => false]);
        }

        return true;
    }

    public function unreadChatMessagesForChatRoom(ChatRoom $chatRoom)
    {
        return $this->chatMessages()
            ->forChatRoom($chatRoom)
            ->ofMessageType(ChatMessageType::NEW_MESSAGE)
            ->incomingFor($this)
            ->where('read_at', '=', null)
            ->get();
    }

    public function unreadChatMessagesForPartner(Partner $partner)
    {
        return $this->chatMessages()
            ->forPartner($partner)
            ->ofMessageType(ChatMessageType::NEW_MESSAGE)
            ->incomingFor($this)
            ->where('read_at', '=', null)
            ->get();
    }
}
