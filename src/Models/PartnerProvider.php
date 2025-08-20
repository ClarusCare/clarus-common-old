<?php

namespace ClarusSharedModels\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PartnerProvider extends Eloquent
{
    use PreviousTimestampFormat, HasFactory;

    protected $fillable = [
        'partner_id',
        'provider_id',
        'active',
    ];

    /**
     * PartnerProvider belongs to Partner.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function partner()
    {
        return $this->belongsTo(Partner::class);
    }

    /**
     * PartnerProvider belongs to Provider.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function provider()
    {
        return $this->belongsTo(Provider::class);
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
}
