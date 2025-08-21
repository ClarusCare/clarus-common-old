<?php

namespace Clarus\SecureChat\Traits;

use Illuminate\Support\Facades\Auth;
use Clarus\SecureChat\Models\ChatRoom;

trait SecureChatPartner
{
    public function chatRooms()
    {
        return $this->hasMany(ChatRoom::class);
    }

    public function getUnreadMessageCountAttribute()
    {
        return Auth::user()->unreadChatMessagesForPartner($this)->count();
    }
}
