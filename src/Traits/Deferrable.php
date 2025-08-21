<?php

namespace ClarusSharedModels\Traits;

use Carbon\Carbon;
use App\Models\Provider;
use App\Models\DeferredNotification;

trait Deferrable
{
    /**
     * Add to deferred notification queue.
     *
     * @param  string  $notificationJob
     * @param  int  $duration
     * @param  null  $fromSnooze
     * @param  Provider  $provider
     * @param  null  $notificationStream
     * @return \App\Models\DeferredNotification
     */
    public function deferNotification(
        $notificationJob,
        $duration,
        $fromSnooze = null,
        ?Provider $provider = null,
        $notificationStream = null
    ) {
        return $this->deferredNotifications()->create([
            'notification_job'    => $notificationJob,
            'expiration'          => Carbon::now()->addMinutes($duration),
            'from_snooze'         => $fromSnooze,
            'provider_id'         => optional($provider)->id,
            'notification_stream' => $notificationStream,
        ]);
    }

    public function deferredNotifications()
    {
        return $this->morphMany(DeferredNotification::class, 'deferrable');
    }
}
