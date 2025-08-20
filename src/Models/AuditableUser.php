<?php

namespace ClarusSharedModels\Models;

use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

class AuditableUser extends User implements Auditable
{
    use AuditableTrait;
}