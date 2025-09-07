<?php

namespace ClarusCommon\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Audit log event type eloquent model.
 *
 * @author Erik Galloway <egalloway@claruscare.com>
 */
class AuditLogType extends Enumerable
{
    use HasFactory;

    /**
     * Constant representing the user created auditable event type.
     */
    public const USER_CREATED = 'user:created';

    /**
     * Constant representing the user password forgot reset auditable event type.
     */
    public const USER_FORGOT_PASSWORD_RESET = 'user:password:forgot:reset';

    /**
     * Constant representing the user password forgot sent auditable event type.
     */
    public const USER_FORGOT_PASSWORD_SENT = 'user:password:forgot:sent';

    /**
     * Constant representing the user password updated auditable event type.
     */
    public const USER_PASSWORD_UPDATED = 'user:password:updated';

    /**
     * Constant representing the user role assigned auditable event type.
     */
    public const USER_ROLE_ASSIGNED = 'user:role:assigned';

    /**
     * Constant representing the user role revoked auditable event type.
     */
    public const USER_ROLE_REVOKED = 'user:role:revoked';

    /**
     * Constant representing the user updated auditable event type.
     */
    public const USER_UPDATED = 'user:updated';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['name', 'label', 'description'];
}
