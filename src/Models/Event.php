<?php

namespace ClarusSharedModels\Models;

use Illuminate\Support\Carbon;
use ClarusSharedModels\Casts\RecurrenceRuleCast;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Event eloquent model.
 *
 * @method \Illuminate\Database\Eloquent\Builder betweenDatesOrIncomplete($starts, $ends)
 * @method \Illuminate\Database\Eloquent\Builder betweenDatesWithRecurrent(\Carbon\Carbon $starts, \Carbon\Carbon $ends)
 * @method \Illuminate\Database\Eloquent\Builder endsAfter(\Carbon\Carbon $timestamp)
 * @method \Illuminate\Database\Eloquent\Builder incomplete()
 * @method \Illuminate\Database\Eloquent\Builder notRecurrent()
 * @method \Illuminate\Database\Eloquent\Builder past()
 * @method \Illuminate\Database\Eloquent\Builder recurrenceCompleted()
 * @method \Illuminate\Database\Eloquent\Builder recurrent()
 * @method \Illuminate\Database\Eloquent\Builder recurrentBetweenDates(\Carbon\Carbon $starts, \Carbon\Carbon $ends)
 * @method \Illuminate\Database\Eloquent\Builder startsBetween($starts, $ends)
 * @method \Illuminate\Database\Eloquent\Builder startsBefore(\Carbon\Carbon $starts)
 * @method static \Illuminate\Database\Eloquent\Builder betweenDatesOrIncomplete($starts, $ends)
 * @method static \Illuminate\Database\Eloquent\Builder betweenDatesWithRecurrent(\Carbon\Carbon $starts, \Carbon\Carbon $ends)
 * @method static \Illuminate\Database\Eloquent\Builder endsAfter(\Carbon\Carbon $timestamp)
 * @method static \Illuminate\Database\Eloquent\Builder incomplete()
 * @method static \Illuminate\Database\Eloquent\Builder notRecurrent()
 * @method static \Illuminate\Database\Eloquent\Builder past()
 * @method static \Illuminate\Database\Eloquent\Builder recurrenceCompleted()
 * @method static \Illuminate\Database\Eloquent\Builder recurrent()
 * @method static \Illuminate\Database\Eloquent\Builder recurrentBetweenDates(\Carbon\Carbon $starts, \Carbon\Carbon $ends)
 * @method static \Illuminate\Database\Eloquent\Builder startsBetween($starts, $ends)
 * @method static \Illuminate\Database\Eloquent\Builder startsBefore(\Carbon\Carbon $starts)
 */
class Event extends Model
{
    use HasFactory;

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['ends_at'];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'duration'    => 'int',
        'starts_at'   => 'datetime',
        'is_complete' => 'boolean',
        'recurrence'  => RecurrenceRuleCast::class,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'starts_at', 'duration', 'recurrence', 'event_type_id',
        'primary_calendar_id', 'is_complete', 'user_id',
    ];

    /**
     * Get the Calendar model relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function calendars()
    {
        return $this->belongsToMany(Calendar::class)
            ->using(CalendarEvent::class)
            ->withTimestamps();
    }

    /**
     * Update and mark the event as complete.
     *
     * @return $this
     */
    public function complete()
    {
        return tap($this, function (): void {
            $this->update(['is_complete' => true]);
        });
    }

    /**
     * Create a copy of the model in the database.
     *
     * @param  bool  $recurrent
     * @return \App\Models\Event
     */
    public function copyEvent($recurrent = true)
    {
        $recurrence = $recurrent ? $this->recurrence : null;

        $new = self::create(array_merge($this->toArray(), ['recurrence' => $recurrence]));

        $new->calendars()->sync($this->calendars);

        $this->eventables->each(function ($eventable) use ($new): void {
            $eventable->copyForEvent($new);
        });

        $this->groupCoverages->each(function ($coverage) use ($new): void {
            EventGroupCoverage::create([
                'event_id'        => $new->id,
                'provider_id'     => $coverage->provider_id,
                'group_member_id' => $coverage->group_member_id,
                'position'        => $coverage->position,
                'created_at'      => $coverage->created_at,
                'updated_at'      => $coverage->updated_at,
            ]);
        });

        return $new;
    }

    /**
     * Get the Eventable model relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function eventables()
    {
        return $this->hasMany(Eventable::class, 'event_id');
    }

    /**
     * Get the EventException model relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function exceptions()
    {
        return $this->hasMany(EventException::class);
    }

    /**
     * Calculate and get the event's ends at attribute.
     *
     * @return \Illuminate\Support\Carbon
     */
    public function getEndsAtAttribute()
    {
        return $this->starts_at->clone()->addMinutes($this->duration);
    }

    /**
     * Get the until date time of the event.
     *
     * @return \Illuminate\Support\Carbon
     */
    public function getUntil()
    {
        if ($this->isRecurring()) {
            return Carbon::parse($this->recurrence->until);
        }

        return $this->ends_at;
    }

    /**
     * Get the EventGroupCoverage model relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function groupCoverages()
    {
        return $this->hasMany(EventGroupCoverage::class);
    }

    /**
     * Determine if the event has any active requests.
     *
     * @return bool
     */
    public function hasActiveRequests(): bool
    {
        $statusIds = EventRequestStatus::active()->get()->pluck('id');

        $requests = $this->requests()->whereIn('event_request_status_id', $statusIds)->get();

        return $requests->isNotEmpty();
    }

    /**
     * Determine if the event has any pending/incomplete requests.
     *
     * @return bool
     */
    public function hasPendingRequests(): bool
    {
        $statusIds = EventRequestStatus::incomplete()->get()->pluck('id');

        $requests = $this->requests()->whereIn('event_request_status_id', $statusIds)->get();

        return $requests->isNotEmpty();
    }

    /**
     * Determine if the event is complete (based on recurrence value and is complete flag).
     *
     * @return bool
     */
    public function isComplete(): bool
    {
        if ($this->is_complete) {
            return true;
        }

        return false;
    }

    /**
     * Determine if the given Calendar model is the primary calendar.
     *
     * @param  \App\Models\Calendar  $calendar
     * @return bool
     */
    public function isPrimaryCalendar(Calendar $calendar): bool
    {
        return $this->primary_calendar_id === $calendar->id;
    }

    /**
     * Determine if the event has a recurrence.
     *
     * @return bool
     */
    public function isRecurring(): bool
    {
        return $this->recurrence->any();
    }

    /**
     * Get the linked Calendar model relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function linkedCalendars()
    {
        return $this->morphedByMany(Calendar::class, 'eventable')
            ->withPivot('id', 'position')
            ->withTimestamps();
    }

    /**
     * Get the primary Calendar model relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function primaryCalendar()
    {
        return $this->belongsTo(Calendar::class, 'primary_calendar_id');
    }

    /**
     * Get the EventGroupCoverage model relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function providerGroupCoverages()
    {
        return $this->hasMany(EventGroupCoverage::class);
    }

    /**
     * Get the ProviderGroup model relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphedByMany
     */
    public function providerGroups()
    {
        return $this->morphedByMany(ProviderGroup::class, 'eventable')
            ->withPivot('position')
            ->withTimestamps();
    }

    /**
     * Get the Provider model relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function providers()
    {
        return $this->morphedByMany(Provider::class, 'eventable')
            ->withPivot('position')
            ->withTimestamps();
    }

    /**
     * Get the EventRequest model relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function requests()
    {
        return $this->hasMany(EventRequest::class);
    }

    /**
     * Scope the query to only events that started between the given dates or is incomplete.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Support\Carbon  $starts
     * @param  \Illuminate\Support\Carbon  $ends
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeBetweenDatesOrIncomplete(Builder $query, $starts, $ends)
    {
        return $query->where(function (Builder $query) use ($starts, $ends): void {
            $query->where(function (Builder $query) use ($starts, $ends): void {
                $query->startsBetween($starts, $ends);
            });

            $query->orWhere(function (Builder $query) use ($starts): void {
                $query
                    ->incomplete()
                    ->startsBefore($starts)
                    ->endsAfter($starts);
            });
        });
    }

    /**
     * Scope the query to all events between the given dates.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Support\Carbon  $starts
     * @param  \Illuminate\Support\Carbon  $ends
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeBetweenDatesWithRecurrent(Builder $query, $starts, $ends)
    {
        return $query->where(function ($query) use ($starts, $ends) {
            return $query
                ->where(fn ($query)   => $query->betweenDatesOrIncomplete($starts, $ends))
                ->orWhere(fn ($query) => $query->recurrentBetweenDates($starts, $ends));
        });
    }

    /**
     * Scope the query to only events that end after the given timestamp.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Support\Carbon  $timestamp
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeEndsAfter(Builder $query, $timestamp)
    {
        $sql = "(events.starts_at + (events.duration * interval'1 minute'))";

        return $query->where(DB::raw($sql), '>=', $timestamp);
    }

    /**
     * Scope the query to events that are not marked as complete.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeIncomplete(Builder $query)
    {
        return $query->where('is_complete', false);
    }

    /**
     * Scope the query to only no recurrent events.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeNotRecurrent(Builder $query)
    {
        return $query->whereNull('recurrence');
    }

    /**
     * Scope the query to events ended in the past.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePast(Builder $query)
    {
        return $query
            ->notRecurrent()
            ->where(
                DB::raw('events.starts_at + (duration * interval\'1 minute\')'),
                '<',
                now()
            );
    }

    /**
     * Scope the query to only events with completed recurrence.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRecurrenceCompleted(Builder $query)
    {
        return $query
            ->recurrent()
            ->where(
                DB::raw("CAST(recurrence->>'until' AS timestamp)"),
                '<',
                now()
            );
    }

    /**
     * Scope the query to only recurrent events.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRecurrent(Builder $query)
    {
        return $query->whereNotNull('recurrence');
    }

    /**
     * Scope the query to only recurrent events that occur between the given dates.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Support\Carbon  $starts
     * @param  \Illuminate\Support\Carbon  $ends
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRecurrentBetweenDates(Builder $query, $starts, $ends)
    {
        return $query
            ->recurrent()
            ->where(function ($query) use ($starts, $ends) {
                return $query
                    ->where('starts_at', '<', $ends)
                    ->where(function ($query) use ($starts) {
                        return $query
                            ->where('starts_at', '>', $starts)
                            ->orWhere(
                                DB::raw("CAST(recurrence->>'until' AS timestamp)"),
                                '>=',
                                $starts
                            );
                    });
            })
            ->orWhere(function ($query) use ($starts, $ends) {
                return $query
                    ->where('starts_at', '>', $starts)
                    ->where('starts_at', '<', $ends);
            });
    }

    /**
     * Scope the query to only events that started before the given timestamp.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Support\Carbon  $timestamp
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeStartsBefore(Builder $query, $timestamp)
    {
        return $query->whereDate('starts_at', '<', $timestamp);
    }

    /**
     * Scope the query to only events that starts at date is between the given dates.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Support\Carbon  $starts
     * @param  \Illuminate\Support\Carbon  $ends
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeStartsBetween(Builder $query, $starts, $ends)
    {
        return $query
            ->whereDate('starts_at', '>=', $starts)
            ->whereDate('starts_at', '<=', $ends);
    }

    /**
     * Scope the query to recurrent events that ends after the given time.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Support\Carbon  $time
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeUntilAfter($query, $time)
    {
        return $query->recurrent()
            ->where(
                DB::raw("CAST(recurrence->>'until' AS timestamp)"),
                '>=',
                $time
            );
    }

    /**
     * Get the EventType model relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function type()
    {
        return $this->belongsTo(EventType::class, 'event_type_id');
    }

    /**
     * Get the User model relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Perform any actions required after the model boots.
     *
     * @return void
     */
    protected static function booted(): void
    {
        static::created(function (self $event): void {
            $event->calendars()->attach($event->primary_calendar_id);
        });
    }
}
