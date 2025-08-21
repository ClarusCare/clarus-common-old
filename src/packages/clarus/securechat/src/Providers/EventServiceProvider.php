<?php

namespace Clarus\SecureChat\Providers;

use App\Events\UserAttachedToPartners;
use App\Events\UserDetachedFromPartners;
use Clarus\SecureChat\Events\ChatEventOccurred;
use Clarus\SecureChat\Listeners\HandleUserAddedToPartner;
use Clarus\SecureChat\Listeners\HandleChatEventOccurrence;
use Clarus\SecureChat\Listeners\HandleUserRemovedFromPartner;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        ChatEventOccurred::class => [
            HandleChatEventOccurrence::class,
        ],
        UserAttachedToPartners::class => [
            HandleUserAddedToPartner::class,
        ],
        UserDetachedFromPartners::class => [
            HandleUserRemovedFromPartner::class,
        ],
    ];

    /**
     * Register any other events for your application.
     *
     * @return void
     */
    public function boot(): void
    {
        parent::boot();
    }
}
