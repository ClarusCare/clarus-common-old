<?php

namespace ClarusSharedModels\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Event type eloquent model.
 *
 * @author Erik Galloway <egalloway@claruscare.com>
 *
 * @method \Illuminate\Database\Eloquent\Builder linkedCalendar()
 * @method \Illuminate\Database\Eloquent\Builder provider()
 * @method \Illuminate\Database\Eloquent\Builder providerGroup()
 * @method static \Illuminate\Database\Eloquent\Builder linkedCalendar()
 * @method static \Illuminate\Database\Eloquent\Builder provider()
 * @method static \Illuminate\Database\Eloquent\Builder providerGroup()
 */
class EventType extends Model
{
    use HasFactory;

    /**
     * Constant representing a linked partner event type.
     */
    public const LINKED_CALENDAR = 'linked-calendar';

    /**
     * Constant representing a provider event type.
     */
    public const PROVIDER = 'provider';

    /**
     * Constant representing a provider group event type.
     */
    public const PROVIDER_GROUP = 'provider-group';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['name', 'label'];

    /**
     * Get the Event model relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function events()
    {
        return $this->hasMany(Event::class);
    }

    /**
     * Determine if the event type is a linked calendar event type.
     *
     * @return bool
     */
    public function isLinkedCalendarEvent(): bool
    {
        return $this->name === static::LINKED_CALENDAR;
    }

    /**
     * Determine if the event type is a provider event type.
     *
     * @return bool
     */
    public function isProviderEvent(): bool
    {
        return $this->name === static::PROVIDER;
    }

    /**
     * Determine if the event type is a provider group event type.
     *
     * @return bool
     */
    public function isProviderGroupEvent(): bool
    {
        return $this->name === static::PROVIDER_GROUP;
    }

    /**
     * Determine if the event type is a requestable (CMC) event type.
     *
     * @return bool
     */
    public function isRequestableEvent(): bool
    {
        return in_array($this->name, [
            static::PROVIDER,
            static::PROVIDER_GROUP,
        ]);
    }

    /**
     * Scope the query to only the linked calendar event type.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeLinkedCalendar(Builder $query)
    {
        return $query->where('name', static::LINKED_CALENDAR);
    }

    /**
     * Scope the query to only the provider event type.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeProvider(Builder $query)
    {
        return $query->where('name', static::PROVIDER);
    }

    /**
     * Scope the query to only the provider group event type.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeProviderGroup(Builder $query)
    {
        return $query->where('name', static::PROVIDER_GROUP);
    }
}
