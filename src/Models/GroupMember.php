<?php

namespace ClarusCommon\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * GroupMember eloquent model.
 */
class GroupMember extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['provider_id', 'provider_group_id', 'position'];

    /**
     * Get the EventGroupCoverage model relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function eventCoverages()
    {
        return $this->hasMany(EventGroupCoverage::class);
    }

    /**
     * Get the Provider model relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function provider()
    {
        return $this->belongsTo(Provider::class);
    }

    /**
     * Get the ProviderGroup model relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function providerGroup()
    {
        return $this->belongsTo(ProviderGroup::class);
    }
}
