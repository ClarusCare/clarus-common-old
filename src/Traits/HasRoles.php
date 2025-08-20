<?php

namespace ClarusSharedModels\Traits;

use ClarusSharedModels\Models\Role;
// use ClarusSharedModels\Models\Partner;
// use ClarusSharedModels\Models\RoleUser;

// Use string references for models to avoid dependency issues
// Projects should have these models: Role, Partner, RoleUser

/**
 * RoleUser eloquent trait model.
 *
 * @author Michael McDowell <mmcdowell@claruscare.com>
 */
trait HasRoles
{
    /**
     * Assign a given role to the user.
     *
     * @param  \App\Models\Role|int|string  $role
     * @param  \App\Models\Partner|int|null  $partner
     * @return $this
     */
    public function assignRole($role, $partner = null)
    {
        if (is_numeric($role)) {
            $roleClass = 'ClarusSharedModels\\Models\\Role';
            $role = $roleClass::find((int) $role);
        }

        if (is_string($role)) {
            $roleClass = 'ClarusSharedModels\\Models\\Role';
            $role = $roleClass::where('name', $role)->first();
        }

        if (is_numeric($partner)) {
            $partnerClass = 'ClarusSharedModels\\Models\\Partner';
            $partner = $partnerClass::find($partner);
        }

        if ($this->hasRole($role, $partner)) {
            return $this;
        }

        $attributes = $partner ? ['partner_id' => $partner->id] : [];

        $this->roles()->attach($role, $attributes);

        return $this;
    }

    /**
     * Get the partners for whom the user is authorized as a partner admin.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getPartnerAdminPartners()
    {
        $roleClass = 'ClarusSharedModels\\Models\\Role';
        $role = $roleClass::firstWhere('name', $roleClass::PARTNER_ADMIN);

        return $this
            ->partners()
            ->whereHas('roles', function ($query) use ($role): void {
                $query
                    ->where('role_user.user_id', $this->id)
                    ->where('role_user.role_id', $role->id);
            })
            ->get();
    }

    /**
     * Determine if a user has a given role.
     *
     * @param  \App\Models\Role|int|string  $role
     * @param  \App\Models\Partner|int|null  $partner
     * @return bool
     */
    public function hasRole($role, $partner = null): bool
    {
        if ($partner instanceof Partner) {
            $partner = $partner->id;
        }

        if (is_numeric($role)) {
            $role = Role::find($role);
        }

        if ($role instanceof Role) {
            $role = $role->name;
        }

        if (! $this->exists && $this->relationLoaded('roles')) {
            return $this->hasRoleInMemory($role, $partner);
        }

        return $this->roles()
            ->whereIn('name', (array) $role)
            ->when($partner, function ($query, $partner): void {
                $query->where('role_user.partner_id', $partner);
            })
            ->exists();
    }

    /**
     * Check if user model has the given role in memory.
     *
     * @param  string  $name
     * @param  int|null  $partnerId
     * @return bool
     */
    public function hasRoleInMemory(string $name, $partnerId = null)
    {
        return $this->roles->contains(function ($role) use ($name, $partnerId) {
            if ($role->is_partner_role && ! $partnerId) {
                return false;
            }

            if (! $role->is_partner_role) {
                return $role->name === $name;
            }

            return $role->name === $name
                && $role->pivot->partner_id === $partnerId;
        });
    }

    /**
     * Check if the model has the admin role.
     *
     * @return bool
     */
    public function isAdmin()
    {
        return $this->hasRole(Role::ADMIN);
    }

    /**
     * Determine if the user is not an administrator.
     *
     * @return bool
     */
    public function isNotAdmin()
    {
        return ! $this->isAdmin();
    }

    /**
     * Determine if the user is an office manager at a given partner.
     *
     * @param  \App\Models\Partner|int|null  $partner
     * @return bool
     */
    public function isOfficeManager($partner = null): bool
    {
        if (! $partner) {
            return false;
        }

        if (is_numeric($partner)) {
            $partner = Partner::find($partner);
        }

        return $this->hasRole(Role::OFFICE_MANAGER, $partner);
    }

    /**
     * Determines if the user if a partner admin or office manager at the given partner.
     *
     * @param  int|\App\Models\Partner  $partner
     * @return bool
     */
    public function isOfficeManagerOrPartnerAdmin($partner)
    {
        if (! $partner) {
            return false;
        }

        if (is_numeric($partner)) {
            $partner = Partner::find($partner);
        }

        return $this->hasRole([Role::OFFICE_MANAGER, Role::PARTNER_ADMIN], $partner);
    }

    /**
     * Determine if the user is a partner admin at a given partner.
     *
     * @param  \App\Models\Partner|int|null  $partner
     * @return bool
     */
    public function isPartnerAdmin($partner = null): bool
    {
        if (! $partner) {
            return false;
        }

        return $this->hasRole(Role::PARTNER_ADMIN, $partner);
    }

    /**
     * Check if the model has the transcriptionist role.
     *
     * @return bool
     */
    public function isTranscriptionist()
    {
        return $this->hasRole(Role::TRANSCRIPTIONIST);
    }

    /**
     * Check if the model has the transcriptionist-manager role.
     *
     * @return bool
     */
    public function isTranscriptionistManager()
    {
        return $this->hasRole(Role::TRANSCRIPTIONIST_MANAGER);
    }

    /**
     * Check if the model has the transcriptionist-qa role.
     *
     * @return bool
     */
    public function isTranscriptionistQA()
    {
        return $this->hasRole(Role::TRANSCRIPTIONIST_QA);
    }

    /**
     * Revoke a given role from the user.
     *
     * @param  \App\Models\Role|string|int  $role
     * @param  \App\Models\Partner|int|null  $partner
     * @return void
     */
    public function revokeRole($role, $partner = null): void
    {
        if (is_string($role)) {
            $role = Role::where('name', $role)->first();
        }

        if (is_numeric($role)) {
            $role = Role::find($role);
        }

        if (is_numeric($partner)) {
            $partner = Partner::find($partner);
        }

        RoleUser::where('role_id', $role->id)
            ->where('role_user.user_id', $this->id)
            ->when($partner, function ($query, $partner): void {
                $query->where('role_user.partner_id', $partner->id);
            })
            ->delete();
    }

    /**
     * Get the Role model relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function roles()
    {
        return $this->belongsToMany('ClarusSharedModels\\Models\\Role', 'role_user')
            ->using('ClarusSharedModels\\Models\\RoleUser')
            ->withPivot('id', 'partner_id')
            ->withTimestamps();
    }

    /**
     * Scope the query to those with the ADMIN role.
     *
     * @param  \Illuminate\Database\Query\Builder $query
     * @return \Illuminate\Database\Query\Builder
     */
    public function scopeAdmin($query)
    {
        return $query->whereHas('roles', function ($query) {
            return $query->where('roles.name', Role::ADMIN);
        });
    }
}
