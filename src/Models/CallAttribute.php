<?php

namespace ClarusSharedModels\Models;

use Illuminate\Database\Eloquent\Model;

class CallAttribute extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'internal_name',
        'display_name',
        'display_order',
        'display_always',
        'min_length',
        'max_length',
        'data_type',
        'data_format'
    ];

    /**
     * Get the partner call attributes for this call attribute.
     */
    public function partnerCallAttributes()
    {
        return $this->hasMany(PartnerCallAttribute::class);
    }
}