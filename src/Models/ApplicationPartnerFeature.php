<?php

namespace ClarusSharedModels\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * ApplicationPartnerFeature eloquent model.
 *
 * @author Erik Galloway <egalloway@claruscare.com>
 */
class ApplicationPartnerFeature extends Pivot
{
    /**
     * Get the Feature model relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function feature()
    {
        return $this->belongsTo(Feature::class);
    }
}
