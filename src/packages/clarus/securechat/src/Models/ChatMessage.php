<?php

namespace Clarus\SecureChat\Models;

use Log;
use App\Models\User;
use App\Models\Partner;
use App\Models\PreviousTimestampFormat;
use Illuminate\Database\Eloquent\Model;

class ChatMessage extends Model
{
    use PreviousTimestampFormat;

    public static $rules = [];

    public static $updateRules = [];

    protected $appends = [
        'from',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
    ];

    protected $fillable = [
        'content',
        'user_id',
        'chat_room_id',
        'type',
    ];

    protected $hidden = [
        'user',
    ];

    public function buildPusherPayload()
    {
        return [
            'message_id'   => $this->id,
            'chat_room_id' => $this->chat_room_id,
            'type'         => $this->type,
        ];
    }

    public function chatRoom()
    {
        return $this->belongsTo(ChatRoom::class);
    }

    public function getFromAttribute()
    {
        return $this->user->full_name;
    }

    public function scopeForChatRoom($query, ChatRoom $chatRoom)
    {
        return $query->where('chat_room_id', '=', $chatRoom->id);
    }

    public function scopeForPartner($query, Partner $partner)
    {
        return $query
            ->whereIn('chat_room_id', function ($query) use ($partner): void {
                $query->select('id')->from('chat_rooms')->where('partner_id', '=', $partner->id);
            });
    }

    public function scopeIncomingFor($query, User $user)
    {
        return $query->where('chat_messages.user_id', '!=', $user->id);
    }

    public function scopeOfMessageType($query, $type)
    {
        return $query->where('type', '=', $type);
    }

    /**
     * @param $user
     * @param  bool  $markAsRead  If the pivot record should be marked as read
     * @param  bool  $checkRoomAccess  Check to see if a user has access to the room before syncing messages
     * @return mixed `false` if the sync could not be completed; otherwise returns the sync result
     */
    public function syncForRoomUser($user, $markAsRead = false, $checkRoomAccess = true)
    {
        if ($checkRoomAccess) {
            if (! $this->chatRoom->userInRoom($user)) {
                Log::info("ChatMessage#syncForRoomUser: User {$user->id} does not have access to the room associated with this message ({$this->id})!");

                return false;
            }
        }

        $data = [];
        $data[$this->id] = ['read_at' => $markAsRead ? date('Y-m-d H:i:s') : null];

        return $user->chatMessages()->syncWithoutDetaching($data);
    }

    /**
     * Will add a pivot record for this message for each user in the room.
     *
     * @param  bool  $markAsRead  If the pivot record should be marked as read
     */
    public function syncForRoomUsers($markAsRead = false): void
    {
        foreach ($this->chatRoom->users as $user) {
            $this->syncForRoomUser($user, $markAsRead, false);
        }
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
