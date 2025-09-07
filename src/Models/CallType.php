<?php

namespace ClarusCommon\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CallType extends Model
{
    use HasFactory, SoftDeletes, PreviousTimestampFormat;

    public $availableNotificationIntervalOptions = [
        5  => 5,
        10 => 10,
        15 => 15,
        30 => 30,
        60 => 60,
        90 => 90,
    ];

    /**
     * The available notification interval options.
     *
     * @var int[]
     */
    public static $intervals = [5, 10, 15, 30, 60, 90];

    public static $rules = [
        'name'                      => 'required',
        'notification_interval'     => 'required',
        'notification_scheme'       => 'required',
        'notification_period_start' => 'required_with:notification_period_end',
        'notification_period_end'   => 'required_with:notification_period_start',
    ];

    /**
     * The available notification scheme options.
     *
     * @var string[]
     */
    public static $schemes = [
        'no_notifications',
        'primary_provider_first',
        'secondary_provider_first',
    ];

    protected $casts = [
        'batch_notifications' => 'boolean',
        'options' => 'object', // add this line
    ];

    protected $fillable = [
        'partner_id',
        'name',
        'batch_notifications',
        'notification_interval',
        'notification_scheme',
        'notification_period_start',
        'notification_period_end',
        'options',
    ];

    protected $guarded = [];

    protected $hidden = [];

    public function calls()
    {
        return $this->belongsToMany(Call::class, 'call_call_type', 'call_type_id', 'call_id');
    }

    /**
     * Expects $time in format 'H:i'.
     *
     * @param $time
     * @param  null  $timezone
     * @return false|null|string
     */
    public function convertTimeToUtcString($time, $timezone = null)
    {
        if (! $time) {
            return $time;
        }

        if (is_null($timezone)) {
            $timezone = $this->partner->timezone;
        }

        [$hours, $minutes] = explode(':', $time);

        $local = now()->setTimezone($timezone)->setTime($hours, $minutes);

        return $local->setTimezone('UTC')->format('H:i');
    }

    /**
     * Get the notification period end attribute in UTC time.
     *
     * @return string|null
     */
    public function getNotificationPeriodEndUtcAttribute()
    {
        return $this->convertTimeToUtcString($this->notification_period_end);
    }

    /**
     * Get the notification period start attribute in UTC time.
     *
     * @return string|null
     */
    public function getNotificationPeriodStartUtcAttribute()
    {
        return $this->convertTimeToUtcString($this->notification_period_start);
    }

    public function getOptionsAttribute()
    {
        if (isset($this->attributes['options'])) {
            return json_decode($this->attributes['options']);
        }
    }

    public function minutesToNotificationPeriodStart()
    {
        if (! $this->notification_period_start) {
            return;
        }

        [$startHour, $startMinute] = explode(':', $this->notification_period_start_utc);

        if (! $startHour || ! $startMinute) {
            return;
        }

        $start = Carbon::createFromTime($startHour, $startMinute);
        $now = Carbon::now();

        if ($start->lt($now)) {
            $start->addDay();
        }

        $diff = $now->diffInMinutes($start);

        return $diff > 5 ? $diff : 5;
    }

    public function partner()
    {
        return $this->belongsTo(Partner::class);
    }
}
