<?php

namespace ClarusSharedModels\Models;

use NumberFormatter;
use App\Traits\Deferrable;
use Illuminate\Database\Eloquent\Model as Eloquent;

class NotificationGroup extends Eloquent
{
    use Deferrable, PreviousTimestampFormat;

    public static $rules = [];

    protected $guarded = [];

    public function calls()
    {
        return $this->belongsToMany(Call::class, 'call_notification_group');
    }

    public function callType()
    {
        return $this->belongsTo(CallType::class);
    }

    public function getOrdinalAttemptedNotifications()
    {
        $formatter = new NumberFormatter('en', NumberFormatter::ORDINAL);

        return $formatter->format($this->notifications_attempted);
    }

    public function provider()
    {
        return $this->belongsTo(Provider::class);
    }
}
