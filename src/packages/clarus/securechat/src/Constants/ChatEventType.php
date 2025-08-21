<?php

namespace Clarus\SecureChat\Constants;

class ChatEventType
{
    public const INVITATION_ACCEPTED = 'invitation_accepted';

    public const INVITATION_CANCELLED = 'invitation_cancelled';

    public const INVITATION_DECLINED = 'invitation_declined';

    public const INVITATION_SENT = 'invitation_sent';

    public const MESSAGE = 'message';

    public const ROOM_JOINED = 'room_joined';

    public const ROOM_LEFT = 'room_left';

    public const ROOM_RENAME = 'room_rename';

    public const USER_ADDED_TO_PARTNER = 'user_added_to_partner';

    public const USER_REMOVED_FROM_PARTNER = 'user_removed_from_partner';

    public const USER_STATUS = 'user_status';
}
