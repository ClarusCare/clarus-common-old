<?php

namespace Clarus\SecureChat\Models;

use App\Models\User;
use App\Models\PreviousTimestampFormat;
use Illuminate\Database\Eloquent\Model;

class ChatUserProfile extends Model
{
    use PreviousTimestampFormat;

    public static $rules = [];

    public static $updateRules = [];

    protected $casts = [
        'data' => 'array',
    ];

    protected $fillable = [
        'user_id',
        'status',
        'data',
    ];

    protected $hidden = [];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
