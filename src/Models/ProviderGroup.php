<?php

namespace ClarusSharedModels\Models;

use ClarusSharedModels\Traits\Eventable;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * ProviderGroup eloquent model.
 *
 * @author Erik Galloway <egalloway@claruscare.com>
 */
class ProviderGroup extends Eloquent
{
    use Eventable, HasFactory, PreviousTimestampFormat;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['name', 'default'];

    /**
     * Get the Provider model relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function memberProviders()
    {
        return $this->belongsToMany(Provider::class, 'group_members')
            ->withPivot('id')
            ->withTimestamps()
            ->withPivot('position')
            ->orderByPivot('position');
    }

    /**
     * Get the GroupMember model relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function members()
    {
        return $this->hasMany(GroupMember::class);
    }

    /**
     * Get the Partner model relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function partner()
    {
        return $this->belongsTo(Partner::class);
    }

    /**
     * Get the Provider model relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function providers()
    {
        return $this->belongsToMany(Provider::class, 'group_members')
            ->withPivot('id')
            ->withTimestamps()
            ->withPivot('position')
            ->orderByPivot('position');
    }

    public static function rules($id = null, $partnerID = null)
    {
        $validations = [
            'required',
            $id ? "unique:provider_groups,name,{$id},id"
                : 'unique:provider_groups,name,NULL,id',
        ];

        if ($partnerID) {
            $validations[1] .= ',partner_id,'.$partnerID;
        }

        return [
            'name' => $validations,
            'providers' => "required"
        ];
    }

    /**
     * Get the IVRModule model relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function ivrModules()
    {
        return $this->hasMany(IvrModule::class);
    }
}
