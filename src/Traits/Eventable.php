<?php

namespace ClarusCommon\Traits;

use App\Models\Event;

/**
 * Eventable eloquent model trait.
 *
 * @author Erik Galloway <egalloway@claruscare.com>
 */
trait Eventable
{
    /**
     * Get the Event model relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function events()
    {
        return $this->morphToMany(Event::class, 'eventable')
            ->withPivot('position')
            ->withTimestamps()
            ->orderByPivot('position');
    }
}
