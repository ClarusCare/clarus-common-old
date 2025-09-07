<?php

namespace ClarusCommon\Models;

use Illuminate\Database\Eloquent\Model;

class PartnerCallAttributeValue extends Model
{
    public $incrementing = false; // No auto-incrementing ID
    protected $primaryKey = null; // No single-column primary key
    public $timestamps = true; // Assuming you use created_at / updated_at

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'call_id',
        'partner_call_attribute_id',
        'value'
    ];

    /**
     * Get the call that owns the attribute value.
     */
    public function call()
    {
        return $this->belongsTo(Call::class);
    }

    /**
     * Get the partner call attribute that owns the value.
     */
    public function partnerCallAttribute()
    {
        return $this->belongsTo(PartnerCallAttribute::class);
    }
}