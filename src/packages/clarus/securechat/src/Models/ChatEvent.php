<?php

namespace Clarus\SecureChat\Models;

use App\Models\User;
use App\Models\PreviousTimestampFormat;
use Illuminate\Database\Eloquent\Model;
use Clarus\SecureChat\Constants\ChatEventType;
use Clarus\SecureChat\Constants\ChatEventScope;

class ChatEvent extends Model
{
    use PreviousTimestampFormat;

    public static $rules = [];

    public static $updateRules = [];

    protected $appends = [];

    protected $broadcastScopes = [
        ChatEventType::MESSAGE                      => ChatEventScope::CHAT_ROOM,
        ChatEventType::ROOM_JOINED                  => ChatEventScope::CHAT_ROOM,
        ChatEventType::ROOM_LEFT                    => ChatEventScope::CHAT_ROOM,
        ChatEventType::ROOM_RENAME                  => ChatEventScope::CHAT_ROOM,

        ChatEventType::INVITATION_SENT              => ChatEventScope::USER,
        ChatEventType::INVITATION_CANCELLED         => ChatEventScope::USER,
        ChatEventType::INVITATION_ACCEPTED          => ChatEventScope::USER,
        ChatEventType::INVITATION_DECLINED          => ChatEventScope::USER,

        ChatEventType::USER_STATUS                  => ChatEventScope::USER_PARTNERS,
        ChatEventType::USER_ADDED_TO_PARTNER        => ChatEventScope::PARTNER,
        ChatEventType::USER_REMOVED_FROM_PARTNER    => ChatEventScope::PARTNER,
    ];

    protected $casts = [
        'data' => 'array',
    ];

    protected $fillable = [
        'user_id',
        'type',
        'data',
    ];

    protected $hidden = [];

    public function buildPusherPayload()
    {
        return [
            'user_id' => $this->user_id,
            'type'    => $this->type,
            'data'    => $this->data,
        ];
    }

    public function getBroadcastScopeAttribute()
    {
        return $this->broadcastScopes[$this->type] ?? null;
    }

    public function getChatMessageIdAttribute()
    {
        if (isset($this->data['chat_message_id'])) {
            return $this->data['chat_message_id'];
        }
    }

    public function getChatRoomIdAttribute()
    {
        if (isset($this->data['chat_room_id'])) {
            return $this->data['chat_room_id'];
        }
    }

    public function getChatRoomInvitationIdAttribute()
    {
        if (isset($this->data['chat_room_invitation_id'])) {
            return $this->data['chat_room_invitation_id'];
        }
    }

    public function getPartnerIdAttribute()
    {
        if (isset($this->data['partner_id'])) {
            return $this->data['partner_id'];
        }
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
