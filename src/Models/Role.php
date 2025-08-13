<?php

namespace ClarusSharedModels\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use ClarusSharedModels\Traits\PreviousTimestampFormat;

/**
 * Role eloquent model.
 *
 * @author Erik Galloway <egalloway@claruscare.com>
 */
class Role extends Model
{
    use HasFactory, PreviousTimestampFormat;

    /**
     * Constant representing the admin role.
     */
    public const ADMIN = 'admin';

    /**
     * Constant representing the office manager role.
     */
    public const OFFICE_MANAGER = 'office_manager';

    /**
     * Constant representing the partner admin role.
     */
    public const PARTNER_ADMIN = 'partner-admin';

    /**
     * Constant representing the transcriptionist role.
     */
    public const TRANSCRIPTIONIST = 'transcriptionist';

    /**
     * Constant representing the transcriptionist manager role.
     */
    public const TRANSCRIPTIONIST_MANAGER = 'transcriptionist-manager';

    /**
     * Constant representing the transcriptionist qa role.
     */
    public const TRANSCRIPTIONIST_QA = 'transcriptionist-qa';

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = ['is_partner_role' => 'bool'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['name'];

    /**
     * Get the User model relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'role_user')
            ->using(RoleUser::class)
            ->withPivot('id', 'partner_id')
            ->withTimestamps();
    }
}
