<?php

namespace ClarusCommon\Models;

use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * TimeBlock eloquent model.
 *
 * @author Daniel Picado <dpicado@claruscare.com>
 *
 * @method \Illuminate\Database\Eloquent\Builder default(bool $default)
 * @method static \Illuminate\Database\Eloquent\Builder default(bool $default)
 */
class TimeBlock extends Model
{
    use HasFactory;

    /**
     * Constant representing the default time block timezone.
     */
    public const DEFAULT_TIMEZONE = 'America/Chicago';

    /**
     * The model's default values for attributes.
     *
     * @var array
     */
    protected $attributes = [
        'name'                 => 'Default',
        'days_of_the_week'     => '[0, 1, 2, 3, 4, 5, 6]',
        'starts_at'            => '09:00:00',
        'ends_at'              => '09:00:00',
        'is_default'           => true,
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'days_of_the_week' => 'array',
        'starts_at'        => 'datetime:H:i:s',
        'ends_at'          => 'datetime:H:i:s',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'days_of_the_week',
        'starts_at',
        'ends_at',
        'is_default',
        'partner_id',
    ];

    /**
     * Get the Calendar model relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function calendars()
    {
        return $this->belongsToMany(Calendar::class)
            ->withTimestamps();
    }

    /**
     * Get the duration (in minutes) of the time block for the given event start date.
     *
     * @param  \Illuminate\Support\Carbon  $starts
     * @return int
     */
    public function getDurationForDate(Carbon $starts)
    {
        return $this->getStartsAtForDate($starts)->diffInMinutes(
            $this->getEndsAtForDate($starts)
        );
    }

    /**
     * Get the ends at attribute (in UTC) as a Carbon instance.
     *
     * @param  string  $value
     * @return \Illuminate\Support\Carbon|null
     */
    public function getEndsAtAttribute($value)
    {
        if (is_null($value)) {
            return $value;
        }

        $ends = Carbon::parse($value, $this->timezone);

        if ($this->starts_at && $this->starts_at->gte($ends)) {
            $ends->addDay();
        }

        return $ends->setTimezone('UTC');
    }

    /**
     * Get the ends at (in UTC) datetime for the given date in UTC.
     *
     * @param  \Illuminate\Support\Carbon  $starts
     * @return \Illuminate\Support\Carbon
     */
    public function getEndsAtForDate(Carbon $starts)
    {
        $timeBlockEnds = $this->ends_at->setDateFrom($starts);

        if ($timeBlockEnds->lte($this->getStartsAtForDate($starts))) {
            $timeBlockEnds->addDay();
        }

        return $timeBlockEnds;
    }

    /**
     * Get the ends at attribute in the partner's local timezone.
     *
     * @return \Illuminate\Support\Carbon|null
     */
    public function getEndsAtLocalAttribute()
    {
        $value = $this->getAttribute('ends_at');

        if (is_null($value)) {
            return $value;
        }

        return $value->setTimezone($this->timezone);
    }

    /**
     * Get the starts at (in UTC) attribute as a Carbon instance.
     *
     * @param  string  $value
     * @return \Illuminate\Support\Carbon|null
     */
    public function getStartsAtAttribute($value)
    {
        if (is_null($value)) {
            return $value;
        }

        return Carbon::parse($value, $this->partner->timezone)->setTimezone('UTC');
    }

    /**
     * Get the starts at datetime for the given date in UTC.
     *
     * @param  \Illuminate\Support\Carbon  $starts
     * @return \Illuminate\Support\Carbon
     */
    public function getStartsAtForDate(Carbon $starts)
    {
        return $this->starts_at->setDateFrom($starts);
    }

    /**
     * Get the starts at attribute in the partner's local timezone.
     *
     * @return \Illuminate\Support\Carbon|null
     */
    public function getStartsAtLocalAttribute()
    {
        $value = $this->getAttribute('starts_at');

        if (is_null($value)) {
            return $value;
        }

        return $value->setTimezone($this->timezone);
    }

    /**
     * Get the timezone attribute.
     *
     * @return string
     */
    public function getTimezoneAttribute()
    {
        return optional($this->partner)->timezone ?: static::DEFAULT_TIMEZONE;
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
     * Scope the query by default or non-default time blocks.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  bool  $default
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDefault(Builder $query, $default = true)
    {
        return $query->where('is_default', $default);
    }

    /**
     * Get the daylight savings offset difference compared to the current time.
     *
     * @param  \Illuminate\Support\Carbon  $time
     * @param  string  $timezone
     * @return int
     */
    public static function getDaylightSavingDifference($time, $timezone)
    {
        $time = $time->clone()->timezone($timezone);

        $now = now()->timezone($timezone);

        return $now->utcOffset() - $time->utcOffset();
    }
}
