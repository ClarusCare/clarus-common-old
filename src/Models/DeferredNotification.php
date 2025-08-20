<?php

namespace ClarusSharedModels\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;

class DeferredNotification extends Eloquent
{
    use PreviousTimestampFormat;

    protected $dates = ['expiration'];

    protected $fillable = [
        'notification_job',
        'expiration',
        'deferrable_type',
        'deferrable_id',
        'from_snooze',
        'provider_id',
        'notification_stream',
    ];

    protected $table = 'deferred_notifications';

    public function deferrable()
    {
        return $this->morphTo();
    }

    public function provider()
    {
        return $this->belongsTo(Provider::class);
    }
}
