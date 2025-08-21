<?php

namespace Clarus\SecureChat\Models;

use App\Models\User;
use App\Models\Partner;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\PreviousTimestampFormat;
use Illuminate\Database\Eloquent\Model;
use Clarus\SecureChat\Constants\ChatEventType;
use Clarus\SecureChat\Events\ChatEventOccurred;
use Clarus\SecureChat\Constants\ChatMessageType;

class ChatRoom extends Model
{
    use PreviousTimestampFormat;

    public static $rules = [];

    public static $updateRules = [];

    protected $appends = ['latest_message', 'unread_message_count'];

    protected $fillable = [
        'name',
        'channel_name',
        'private',
        'user_id',
        'partner_id',
    ];

    protected $hidden = [];

    public function activeUsers()
    {
        return $this->belongsToMany(User::class, 'chat_room_user')->wherePivot('active', true);
    }

    /**
     * Add user to chat room.
     *
     * @param  User  $user
     * @return bool
     */
    public function addUser(User $user)
    {
        if (! $this->userInRoom($user)) {
            $this->users()->attach($user->id);
            event(new ChatEventOccurred($user->id, ChatEventType::ROOM_JOINED, ['chat_room_id' => $this->id]));
            Log::info("User {$user->id} added to chat room {$this->id}.");
        } else {
            Log::info("User {$user->id} already added to chat room {$this->id}.");
        }

        return true;
    }

    public function getLatestMessageAttribute()
    {
        return $this->messages()
            ->where('type', '=', ChatMessageType::NEW_MESSAGE)
            ->orderBy('id', 'desc')
            ->first();
    }

    public function getUnreadMessageCountAttribute()
    {
        return Auth::user()->unreadChatMessagesForChatRoom($this)->count();
    }

    /**
     * Gets the current status of a room's user.
     *
     * @param  User  $user
     * @return bool
     */
    public function isUserActive(User $user)
    {
        $roomUser = $this->users()->withPivot('active')->where('users.id', $user->id)->first();

        return $roomUser->pivot->active;
    }

    public function messages()
    {
        return $this->hasMany(ChatMessage::class);
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function partner()
    {
        return $this->belongsTo(Partner::class, 'partner_id');
    }

    /**
     * Remove user from chat room.
     *
     * @param  User  $user
     * @return bool
     */
    public function removeUser(User $user)
    {
        if ($this->userInRoom($user)) {
            $this->users()->detach($user->id);
            event(new ChatEventOccurred($user->id, ChatEventType::ROOM_LEFT, ['chat_room_id' => $this->id]));
            Log::info("User {$user->id} removed from chat room {$this->id}.");
        } else {
            Log::info("User {$user->id} already removed from chat room {$this->id}.");
        }

        return true;
    }

    /**
     * Syncs all messages in the room for a user.
     *
     * @param  User  $user
     * @param  bool  $markAsRead
     */
    public function syncAllMessagesForUser(User $user, $markAsRead = false): void
    {
        foreach ($this->messages as $message) {
            $message->syncForRoomUser($user, $markAsRead);
        }
    }

    /**
     * Check if user is in room.
     *
     * @param $user
     * @return bool
     */
    public function userInRoom($user)
    {
        $userId = $user instanceof User ? $user->id : $user;

        return in_array($userId, $this->users()->pluck('user_id')->toArray());
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'chat_room_user');
    }
}
