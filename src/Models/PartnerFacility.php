<?php

namespace ClarusCommon\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartnerFacility extends Model
{
    use PreviousTimestampFormat;

    public static $rules = [
        'name'       => 'required',
        'partner_id' => 'required|int|min:1',
    ];

    protected $fillable = [
        'partner_id',
        'name',
    ];

    protected $guarded = [
        'created_at',
        'updated_at',
    ];

    /**
     * PartnerFacility belongs to Partner.
     *
     * @return BelongsTo
     */
    public function partner()
    {
        return $this->belongsTo(Partner::class);
    }
}
