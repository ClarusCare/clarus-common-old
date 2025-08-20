<?php

namespace ClarusSharedModels\Models;

use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * PartnerSchedule eloquent model.
 *
 * @author Michael McDowell <mmcdowell@claruscare.com>
 */
class PartnerSchedule extends Model
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
        'starts_at'   => 'immutable_datetime',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'partner_id',
        'day_name',
        'starts_at',
        'ends_at',
        'duration',
    ];

    /**
     * Get the CalendarDay model relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function day()
    {
        return $this->belongsTo(CalendarDay::class, 'day_name');
    }

    /**
     * Calculate and get the event's ends at attribute.
     *
     * @return \Illuminate\Support\Carbon
     */
    public function getEndsAtAttribute()
    {
        if ($this->starts_at && $this->duration > 0) {
            return $this->starts_at->addMinutes($this->duration);
        }
    }

    /**
     * Get the ends at attribute in the partner's local timezone.
     *
     * @return \Carbon\CarbonImmutable
     */
    public function getEndsAtLocalAttribute()
    {
        return new CarbonImmutable(
            $this->ends_at->format('Y-m-d H:i:s'),
            $this->partner->timezone
        );
    }

    /**
     * Get the starts at attribute in the partner's local timezone.
     *
     * @return \Carbon\CarbonImmutable
     */
    public function getStartsAtLocalAttribute()
    {
        return new CarbonImmutable(
            $this->starts_at->format('Y-m-d H:i:s'),
            $this->partner->timezone
        );
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
     * Set the duration when the ends at attribute is filled.
     *
     * @param  \Illuminate\Support\Carbon|string  $value
     * @return mixed
     */
    public function setEndsAtAttribute($value)
    {
        if (! $this->starts_at || ! $value) {
            return $this->attributes['duration'] = 0;
        }

        if (is_string($value)) {
            $value = Carbon::parse($value);
        }

        if ($value->lessThanOrEqualTo($this->starts_at)) {
            $value->addDay();
        }

        $this->attributes['duration'] = $this->starts_at->diffInMinutes($value);
    }
}
