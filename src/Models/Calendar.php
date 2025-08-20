<?php

namespace ClarusSharedModels\Models;

use App\Calendars\EventResolver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Calendar Eloquent model.
 *
 * @author Erik Galloway <egalloway@claruscare.com>
 *
 * @method Builder active(bool $active = true) Scope the query to active calendars.
 * @method Builder default(bool $default = true) Scope the query to default calendars.
 */
class Calendar extends Model
{
    use HasFactory;

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'is_active'  => 'boolean',
        'is_default' => 'boolean',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['name', 'partner_id', 'is_active', 'is_default'];

    /**
     * Get the Event model relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function events()
    {
        return $this->belongsToMany(Event::class)
            ->using(CalendarEvent::class)
            ->withTimestamps();
    }

    /**
     * Get the current on-going event for the calendar instance.
     *
     * @param  \Illuminate\Support\Carbon|null  $timestamp
     * @return \App\Models\Event|null
     */
    public function getCurrentEvent($timestamp = null)
    {
        return (new EventResolver)->during($timestamp)->resolve($this);
    }

    /**
     * Get the title to display for a calendar event.
     *
     * @return string
     */
    public function getEventTitle()
    {
        if (! $this->relationLoaded('partner')) {
            return $this->name;
        }

        return "{$this->partner->name}: {$this->name}";
    }

    /**
     * Accessor for the title attribute.
     *
     * @return string
     */
    public function getTitleAttribute()
    {
        return $this->getEventTitle();
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

    /**
     * Get the primary calendar Event model relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function primaryEvents()
    {
        return $this->hasMany(Event::class, 'primary_calendar_id');
    }

    /**
     * Get the TimeBlock model relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function timeBlocks()
    {
        return $this
            ->belongsToMany(TimeBlock::class)
            ->withTimestamps();
    }

    /**
     * Scope a query to only include active calendars.
     *
     * @param  Builder  $query
     * @param  bool     $active
     * @return Builder
     */
    public function scopeActive(Builder $query, $active = true)
    {
        return $query->where('is_active', $active);
    }

    /**
     * Scope a query to include only default calendars.
     *
     * @param  Builder  $query
     * @param  bool     $default
     * @return Builder
     */
    public function scopeDefault(Builder $query, $default = true)
    {
        return $query->where('is_default', $default);
    }
}
