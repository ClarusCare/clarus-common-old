<?php

namespace Clarus\SecureChat\Models;

use App\Models\User;
use App\Models\PreviousTimestampFormat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChatRoomInvitation extends Model
{
    use PreviousTimestampFormat;
    use SoftDeletes;

    public static $rules = [];

    public static $updateRules = [];

    protected $dates = ['deleted_at'];

    protected $fillable = [
        'user_id',
        'chat_room_id',
        'created_by_user_id',
    ];

    protected $hidden = [];

    public function chatRoom()
    {
        return $this->belongsTo(ChatRoom::class);
    }

    public function createdByUser()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
