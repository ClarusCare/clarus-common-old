<?php

namespace ClarusSharedModels\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationProfile extends Model
{
    use PreviousTimestampFormat;

    protected $fillable = [
        'email',
        'phone_number',
        'allow_mobile_notifications',
        'allow_email_notifications',
        'allow_voice_notifications',
        'allow_sms_notifications',
        'allow_pager_notifications',
        'enable_primary_phone_number',
        'enable_secondary_phone_number',
        'pager_email_address',
        'pager_phone_number',
        'secondary_phone_number',
        'allow_secondary_sms_notifications',
        'allow_secondary_voice_notifications',
    ];

    protected $table = 'notification_profiles';

    public function notifiable(): void
    {
        $this->morphTo();
    }
}
