<?php

namespace ClarusCommon\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * ApplicationPartner eloquent model.
 *
 * @author Daniel Picado <dpicado@claruscare.com>
 */
class ApplicationPartner extends Pivot
{
    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = true;

    /**
     * Get the Application model relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function application()
    {
        return $this->belongsTo(Application::class);
    }

    /**
     * Get the Feature model relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function features()
    {
        return $this->belongsToMany(Feature::class, 'application_partner_feature', 'application_partner_id')
            ->withTimestamps()
            ->using(ApplicationPartnerFeature::class);
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
}
