<?php

namespace ClarusCommon\Models;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Event request eloquent model.
 *
 * @author Erik Galloway <egalloway@claruscare.com>
 */
class EventRequest extends Model
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
        'starts_at'    => 'datetime',
        'duration'     => 'int',
        'read_at'      => 'datetime',
        'responded_at' => 'datetime',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'event_id', 'starts_at', 'duration', 'sender_id', 'recipient_id', 'covered_id',
        'eventable_id', 'event_request_status_id', 'read_at', 'responded_at',
    ];

    /**
     * Accept a cover my call event request.
     *
     * @return $this
     */
    public function accept()
    {
        $this->responded_at = now();

        $this->status()
            ->associate(EventRequestStatus::accepted()->first())
            ->save();

        return $this;
    }

    /**
     * Cancel a cover my call event request.
     *
     * @return $this
     */
    public function cancel(?EventRequestStatus $status = null)
    {
        if (is_null($status)) {
            $status = EventRequestStatus::canceled()->first();
        }

        $this->status()
            ->associate($status)
            ->save();

        return $this;
    }

    /**
     * Create a copy of the model in the database.
     *
     * @param  \App\Models\Eventable  $eventable
     * @return \App\Models\EventRequest
     */
    public function copyForEventable(Eventable $eventable): void
    {
        $this->update([
            'eventable_id'            => $eventable->id,
            'event_id'                => $eventable->event_id,
            'starts_at'               => $this->starts_at,
            'duration'                => $this->duration,
            'sender_id'               => $this->sender_id,
            'recipient_id'            => $this->recipient_id,
            'event_request_status_id' => $this->event_request_status_id,
            'read_at'                 => $this->read_at,
            'responded_at'            => $this->responded_at,
            'covered_id'              => $this->covered_id,
            'updated_at'              => $this->updated_at,
            'created_at'              => $this->created_at,
        ]);
    }

    /**
     * Get the Provider model relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function covered()
    {
        return $this->belongsTo(Provider::class, 'covered_id');
    }

    /**
     * Decline the cover my call event request.
     *
     * @return $this
     */
    public function decline()
    {
        $this->responded_at = now();

        $this->status()
            ->associate(EventRequestStatus::declined()->first())
            ->save();

        return $this;
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
        return $this->belongsTo(Eventable::class);
    }

    /**
     * Mark a cover my call event request as expired.
     *
     * @return $this
     */
    public function expired(?EventRequestStatus $status = null)
    {
        if (is_null($status)) {
            $status = EventRequestStatus::expired()->first();
        }

        $this->status()
            ->associate($status)
            ->save();

        return $this;
    }

    /**
     * Forward a cover my call event request.
     *
     * @return $this
     */
    public function forward()
    {
        if (! $this->responded_at) {
            $this->responded_at = now();
        }

        $this->status()
            ->associate(EventRequestStatus::forwarded()->first())
            ->save();

        return $this;
    }

    /**
     * Calculate and get the event request's ends at attribute.
     *
     * @return \Illuminate\Support\Carbon
     */
    public function getEndsAtAttribute()
    {
        return $this
            ->starts_at
            ->clone()
            ->addMinutes($this->duration)
            ->startOfMinute();
    }

    /**
     * Determine if the request's status is final/completed.
     *
     * @return bool
     */
    public function isComplete(): bool
    {
        return $this->status->isComplete();
    }

    /**
     * Mark a cover my call event request as pending.
     *
     * @return $this
     */
    public function pending()
    {
        $this->status()
            ->associate(EventRequestStatus::pending()->first())
            ->save();

        return $this;
    }

    /**
     * Get the Provider model relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function recipient()
    {
        return $this->belongsTo(Provider::class, 'recipient_id');
    }

    /**
     * Scopes the query to only accepted request.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return \Illuminate\Database\Query\Builder
     */
    public function scopeAccepted($query)
    {
        return $query->whereHas('status', fn ($query) => $query->accepted());
    }

    /**
     * Scopes the query to only active requests.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return \Illuminate\Database\Query\Builder
     */
    public function scopeActive($query)
    {
        return $query->whereHas('status', fn ($query) => $query->active());
    }

    /**
     * Scope the query to requests during the given time.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  \Illuminate\Support\Carbon  $time
     * @return \Illuminate\Database\Query\Builder
     */
    public function scopeDuringTime($query, $time)
    {
        $sql = DB::raw("(event_requests.starts_at + (event_requests.duration * interval'1 minute'))");

        return $query->where($sql, '>', $time)
            ->where('starts_at', '<=', $time);
    }

    /**
     * Scope the query to only requests that end after the given date.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  \Illuminate\Support\Carbon|string  $ends
     * @return \Illuminate\Database\Query\Builder
     */
    public function scopeEndsAfter($query, $ends)
    {
        $sql = DB::raw("(event_requests.starts_at + (event_requests.duration * interval'1 minute'))");

        return $query->whereDate($sql, '>', $ends);
    }

    /**
     * Scope the query to only requests that end before the given date.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  \Illuminate\Support\Carbon|string  $ends
     * @return \Illuminate\Database\Query\Builder
     */
    public function scopeEndsBefore($query, $ends)
    {
        $sql = DB::raw("(event_requests.starts_at + (event_requests.duration * interval'1 minute'))");

        return $query->whereDate($sql, '<=', $ends);
    }

    /**
     * Scope the query to only requests are within the time frame.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  \Illuminate\Support\Carbon|string  $starts
     * @param  \Illuminate\Support\Carbon|string  $ends
     * @return \Illuminate\Database\Query\Builder
     */
    public function scopeWithinTimeFrame($query, $starts, $ends)
    {
        return $query->where(function ($query) use ($starts, $ends) {
            return $query->where('starts_at', '<', $ends)
                ->where('starts_at', '>=', $starts);
        })->orWhere(function ($query) use ($starts, $ends) {
            return $query->endsAfter($starts)
                ->endsBefore($ends);
        });
    }

    /**
     * Get the Provider model relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function sender()
    {
        return $this->belongsTo(Provider::class, 'sender_id');
    }

    /**
     * Set the starts at attribute (and round to the beginning of the minute).
     *
     * @param  \Carbon\Carbon\string  $starts
     * @return void
     */
    public function setStartsAtAttribute($starts): void
    {
        if (is_string($starts)) {
            $starts = Carbon::parse($starts);
        }

        if ($starts instanceof Carbon) {
            $starts->startOfMinute();
        }

        $this->attributes['starts_at'] = $starts;
    }

    /**
     * Get the EventRequestStatus model relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function status()
    {
        return $this->belongsTo(EventRequestStatus::class, 'event_request_status_id');
    }

    /**
     * Perform any actions required after the model boots.
     *
     * @return void
     */
    protected static function booted(): void
    {
        static::creating(function ($request): void {
            if (! $request->event_request_status_id) {
                $status = EventRequestStatus::pending()->first();

                $request->event_request_status_id = $status->id;
            }
        });
    }
}
