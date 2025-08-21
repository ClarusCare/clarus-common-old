<?php

namespace Clarus\SecureChat\Constants;

class ChatEventScope
{
    /**
     * Event broadcast goes to all users in a particular chat room.
     */
    public const CHAT_ROOM = 'chat_room';

    /**
     * Event broadcast goes to all users assigned to a particular partner.
     */
    public const PARTNER = 'partner';

    /**
     * Event broadcast goes to individual user.
     */
    public const USER = 'user';

    /**
     * Event broadcast goes to all users assigned to all of a particular user's
     * partners.
     */
    public const USER_PARTNERS = 'user_partners';
}
