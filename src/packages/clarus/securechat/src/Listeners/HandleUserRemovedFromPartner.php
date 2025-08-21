<?php

namespace Clarus\SecureChat\Listeners;

use Illuminate\Support\Facades\Log;
use Illuminate\Queue\InteractsWithQueue;
use Clarus\SecureChat\Gateways\UserGateway;
use Illuminate\Contracts\Queue\ShouldQueue;
use Clarus\SecureChat\Constants\ChatEventType;
use Clarus\SecureChat\Events\ChatEventOccurred;

class HandleUserRemovedFromPartner implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * @var UserGateway
     */
    private $users;

    /**
     * NotifyUserRemovedFromPartner constructor.
     *
     * @param  UserGateway  $users
     */
    public function __construct(UserGateway $users)
    {
        $this->users = $users;
    }

    /**
     * Handle the event.
     *
     * @param  $event
     * @return void
     */
    public function handle($event): void
    {
        $user = $this->users->find($event->userId);
        $removedFromPartnerIds = $event->detached;

        Log::info('Handling secure chat user ID '.$user->id.' removed from partner(s).');

        foreach ($removedFromPartnerIds as $partnerId) {
            if (! $user->isAttachedToPartner($partnerId)) {
                event(new ChatEventOccurred($user->id, ChatEventType::USER_REMOVED_FROM_PARTNER, [
                    'user_id'    => $user->id,
                    'partner_id' => $partnerId,
                ]));

                $user->setInactiveInPartnerChatRooms($partnerId);
            } else {
                Log::info("User {$user->id} is still attached to partner {$partnerId}.  No chat event created.");
            }
        }
    }
}
