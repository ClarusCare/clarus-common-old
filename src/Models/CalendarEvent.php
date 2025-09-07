<?php

namespace ClarusCommon\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Calendar event eloquent pivot model.
 *
 * @author Erik Galloway <egalloway@claruscare.com>
 */
class CalendarEvent extends Pivot
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['calendar_id', 'event_id'];

    /**
     * Get the Calendar model relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function calendar()
    {
        return $this->belongsTo(Calendar::class);
    }

    /**
     * Get the Event model relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function event()
    {
        return $this->belongsTo(Event::class);
    }
}
