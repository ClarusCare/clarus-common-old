<?php

namespace ClarusCommon\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;

class PushToken extends Eloquent
{
    use PreviousTimestampFormat;

    public static $rules = [
        'token'  => 'required',
        'device' => 'required',
    ];

    protected $fillable = ['token', 'device', 'service'];

    protected $hidden = ['id', 'updated_at'];

    public function __construct($attributes = [], $exists = false)
    {
        //Set default values
        $this->active = true;

        parent::__construct($attributes, $exists);
    }

    public function provider()
    {
        return $this->belongsTo(\App\Models\Provider::class);
    }

    public function push_notification_logs()
    {
        return $this->hasMany(\App\Models\PushNotificationLog::class);
    }

    public function scopeActive($query)
    {
        return $query->where('active', '=', true);
    }

    public function scopeNewest($query)
    {
        return $query->orderBy('updated_at', 'DESC');
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}
