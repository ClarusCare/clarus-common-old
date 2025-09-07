<?php

namespace ClarusCommon\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;

class PushNotificationLog extends Eloquent
{
    use PreviousTimestampFormat;

    public static $rules = [];

    protected $guarded = [];

    public function provider()
    {
        return $this->belongsTo(\App\Models\Provider::class);
    }

    public function push_token()
    {
        return $this->belongsTo(\App\Models\PushToken::class);
    }
}
