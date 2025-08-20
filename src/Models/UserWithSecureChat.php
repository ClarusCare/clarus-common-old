<?php

namespace ClarusSharedModels\Models;

use Clarus\SecureChat\Traits\SecureChatUser;

class UserWithSecureChat extends User
{
    use SecureChatUser;
}