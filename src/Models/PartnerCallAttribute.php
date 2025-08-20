<?php

namespace ClarusSharedModels\Models;

use Illuminate\Database\Eloquent\Model;

class PartnerCallAttribute extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'partner_id',
        'call_attribute_id',
        'display_order',
        'active'
    ];

    /**
     * Get the partner that owns the attribute.
     */
    public function partner()
    {
        return $this->belongsTo(Partner::class);
    }

    /**
     * Get the call attribute that this partner attribute is based on.
     */
    public function callAttribute()
    {
        return $this->belongsTo(CallAttribute::class);
    }

    /**
     * Get the values for this partner call attribute.
     * This defines a one-to-many relationship where one PartnerCallAttribute can have multiple values
     */
    public function values()
    {
        return $this->hasMany(PartnerCallAttributeValue::class);
    }
}
