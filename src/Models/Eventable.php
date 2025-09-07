<?php

namespace ClarusCommon\Models;

use App\Scopes\OrderByPosition;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphPivot;

/**
 * Eventable eloquent polymorphic pivot model.
 *
 * @author Erik Galloway <egalloway@claruscare.com>
 */
class Eventable extends MorphPivot
{
    use HasFactory;

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = true;

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = ['position' => 'int'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['event_id', 'eventable_type', 'eventable_id', 'position'];

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'eventables';

    /**
     * Copy the eventable on the given event.
     *
     * @param  \App\Models\Event  $event
     * @return \App\Models\Eventable
     */
    public function copyForEvent(Event $event)
    {
        $new = $event->eventables()->create([
            'eventable_type' => $this->eventable_type,
            'eventable_id'   => $this->eventable_id,
            'position'       => $this->position,
        ]);

        if ($event->isRecurring()) {
            $this->requests()
                ->where('starts_at', '>=', $event->starts_at)
                ->get()
                ->each
                ->copyForEventable($new);
        }

        return $new;
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

    /**
     * Get the Eventable model relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function eventable()
    {
        return $this->morphTo();
    }

    /**
     * Get the eventable's name attribute.
     *
     * @return string
     */
    public function getName()
    {
        if ($this->relationLoaded('eventable')) {
            return optional($this->eventable)->name;
        }
    }

    /**
     * Get the EventRequest model relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function requests()
    {
        return $this->hasMany(EventRequest::class, 'eventable_id');
    }

    /**
     * Perform any actions required after the model boots.
     *
     * @return void
     */
    protected static function booted(): void
    {
        static::addGlobalScope(new OrderByPosition);
    }
}
