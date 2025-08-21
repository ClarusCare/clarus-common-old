<?php

namespace Clarus\SecureChat\Listeners;

use Illuminate\Support\Facades\Log;
use Illuminate\Queue\InteractsWithQueue;
use Clarus\SecureChat\Gateways\UserGateway;
use Illuminate\Contracts\Queue\ShouldQueue;
use Clarus\SecureChat\Constants\ChatEventType;
use Clarus\SecureChat\Gateways\PartnerGateway;
use Clarus\SecureChat\Events\ChatEventOccurred;
use Clarus\SecureChat\Gateways\ChatMessageGateway;

class HandleUserAddedToPartner implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * @var ChatMessageGateway
     */
    private $chatMessages;

    /**
     * @var PartnerGateway
     */
    private $partners;

    /**
     * @var UserGateway
     */
    private $users;

    /**
     * NotifyUserAddedToPartner constructor.
     *
     * @param  UserGateway  $users
     * @param  PartnerGateway  $partners
     * @param  ChatMessageGateway  $chatMessages
     */
    public function __construct(UserGateway $users, PartnerGateway $partners, ChatMessageGateway $chatMessages)
    {
        $this->users = $users;
        $this->partners = $partners;
        $this->chatMessages = $chatMessages;
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
        $addedToPartnerIds = $event->attached;

        Log::info('Handling secure chat user ID '.$user->id.' attached to partner(s).');

        foreach ($addedToPartnerIds as $partnerId) {
            $partner = $this->partners->find($partnerId);
            foreach ($partner->chatRooms as $chatRoom) {
                if ($chatRoom->userInRoom($user)) {
                    $this->chatMessages->syncCollectionForUser($chatRoom->messages, $user, true);
                }
            }

            event(new ChatEventOccurred($user->id, ChatEventType::USER_ADDED_TO_PARTNER, [
                'user_id'    => $user->id,
                'partner_id' => $partnerId,
            ]));
        }
    }
}
