<?php

namespace ClarusSharedModels\Models;

use Clarus\SecureChat\Traits\SecureChatUser;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

class FullFeaturedUser extends User implements Auditable
{
    use SecureChatUser, AuditableTrait;
}