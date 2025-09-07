<?php

namespace ClarusCommon\Models;

use Illuminate\Database\Eloquent\Model;

class SmsPasswordReminder extends Model
{
    use PreviousTimestampFormat;

    protected $fillable = [
        'user_id',
        'expire_time',
        'authorization_code',
    ];

    public function purge($userId = null): void
    {
        $this->where('expire_time', '<', time())->delete();

        if ($userId) {
            $this->where('user_id', $userId)->delete();
        }
    }

    public function scopeValid($query)
    {
        return $query->where('expire_time', '>', time());
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
