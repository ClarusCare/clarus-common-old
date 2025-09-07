<?php

namespace ClarusCommon\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Application feature eloquent model.
 *
 * @author Erik Galloway <egalloway@claruscare.com>
 */
class ApplicationFeature extends Pivot
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['application_id', 'feature_id'];

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
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function feature()
    {
        return $this->belongsTo(Feature::class);
    }
}
