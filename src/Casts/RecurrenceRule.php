<?php

namespace ClarusSharedModels\Casts;

use ArrayAccess;
use Carbon\Carbon;
use JsonSerializable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Database\Eloquent\Castable;

/**
 * Recurrence rule cast-value object.
 *
 * @author Erik Galloway <egalloway@claruscare.com>
 */
class RecurrenceRule implements ArrayAccess, Arrayable, Castable, JsonSerializable, Jsonable
{
    /**
     * Constant representing a daily recurrence frequency.
     */
    public const DAILY = 'DAILY';

    /**
     * Constant representing Friday.
     */
    public const FRIDAY = 'FR';

    /**
     * Constant representing a hourly recurrence frequency.
     */
    public const HOURLY = 4;

    /**
     * Constant representing a minutely recurrence frequency.
     */
    public const MINUTELY = 'MINUTELY';

    /**
     * Constant representing Monday.
     */
    public const MONDAY = 'MO';

    /**
     * Constant representing a monthly recurrence frequency.
     */
    public const MONTHLY = 'MONTHLY';

    /**
     * Constant representing Saturday.
     */
    public const SATURDAY = 'SA';

    /**
     * Constant representing a secondly recurrence frequency.
     */
    public const SECONDLY = 'SECONDLY';

    /**
     * Constant representing Monday.
     */
    public const SUNDAY = 'SU';

    /**
     * Constant representing Thursday.
     */
    public const THURSDAY = 'TH';

    /**
     * Constant representing Tuesday.
     */
    public const TUESDAY = 'TU';

    /**
     * Constant representing Wednesday.
     */
    public const WEDNESDAY = 'WE';

    /**
     * Constant representing a weekly recurrence frequency.
     */
    public const WEEKLY = 'WEEKLY';

    /**
     * Constant representing a yearly recurrence frequency.
     */
    public const YEARLY = 'YEARLY';

    /**
     * The notification settings attributes.
     *
     * @var array
     */
    public $attributes = [];

    /**
     * The recurrence day map.
     *
     * @var array
     */
    public static $days = [
        'MONDAY'    => self::MONDAY,
        'TUESDAY'   => self::TUESDAY,
        'WEDNESDAY' => self::WEDNESDAY,
        'THURSDAY'  => self::THURSDAY,
        'FRIDAY'    => self::FRIDAY,
        'SATURDAY'  => self::SATURDAY,
        'SUNDAY'    => self::SUNDAY,
    ];

    /**
     * The recurrence day map.
     *
     * @var array
     */
    public static $daysOfTheWeek = [
        self::SUNDAY,
        self::MONDAY,
        self::TUESDAY,
        self::WEDNESDAY,
        self::THURSDAY,
        self::FRIDAY,
        self::SATURDAY,
    ];

    /**
     * The recurrence frequency map.
     *
     * @var array
     */
    public static $frequencies = [
        self::YEARLY,
        self::MONTHLY,
        self::WEEKLY,
        self::DAILY,
        self::HOURLY,
        self::MINUTELY,
        self::SECONDLY,
    ];

    /**
     * The attribute keys that should be replaced with their new key.
     *
     * Original key => new key that should replace the original.
     *
     * @var array
     */
    protected $replacements = [
        'frequency' => 'freq',
    ];

    /**
     * Make a new RecurrenceRule instance.
     *
     * @param  array  $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->fill($attributes);
    }

    /**
     * Get the raw attributes array.
     *
     * @return array
     */
    public function all(): array
    {
        return $this->attributes;
    }

    /**
     * Determine if any attribute values exist.
     *
     * @return bool
     */
    public function any(): bool
    {
        return count($this->attributes) > 0;
    }

    /**
     * Get or set the week days the event should repeat on.
     *
     * @param  array|null  $days
     * @return $this|array
     */
    public function byWeekDays(?array $days = null)
    {
        if (is_null($days)) {
            return $this->get('byweekday', []);
        }

        return $this->set('byweekday', $days);
    }

    /**
     * Set the recurrence frequency to daily.
     *
     * @return $this
     */
    public function daily()
    {
        return $this->frequency(self::DAILY);
    }

    /**
     * Fill the attributes array with the given key values.
     *
     * @param  array  $attributes
     * @return $this
     */
    public function fill(array $attributes)
    {
        foreach ($attributes as $key => $value) {
            $this->set($key, $value);
        }

        return $this;
    }

    /**
     * Get or set the event's recurrence frequency.
     *
     * @param  string|null  $frequency
     * @return $this|string
     */
    public function frequency(?string $frequency = null)
    {
        if (is_null($frequency)) {
            return $this->get('freq') === static::WEEKLY ? static::WEEKLY : static::DAILY;
        }

        return $this->set('freq', $frequency);
    }

    /**
     * Get the given key's value from the attributes.
     *
     * @param  string  $key
     * @param  mixed  $default
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        if (! $this->has($key)) {
            return $default;
        }

        return $this->attributes[$key];
    }

    /**
     * Get the true key name for the given value.
     *
     * @param  string  $key
     * @return string
     */
    public function getKeyName(string $key)
    {
        if (! array_key_exists($key, $this->replacements)) {
            return $key;
        }

        return $this->replacements[$key];
    }

    /**
     * Determine if the key exists.
     *
     * @param  string  $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->attributes);
    }

    /**
     * Get or set the recurrence interval.
     *
     * @param  int|null  $interval
     * @return $this|int
     */
    public function interval(?int $interval = null)
    {
        if (is_null($interval)) {
            return $this->get('interval');
        }

        return $this->set('interval', $interval);
    }

    /**
     * Determine if the event recurrence happens daily.
     *
     * @return bool
     */
    public function isDaily(): bool
    {
        return $this->get('freq') === self::DAILY;
    }

    /**
     * Determine if the event recurrence happens weekly.
     *
     * @return bool
     */
    public function isWeekly(): bool
    {
        return $this->get('freq') === self::WEEKLY;
    }

    /**
     * Convert the object into something JSON serializable.
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return [
            'freq'      => $this->frequency(),
            'until'     => $this->until(),
            'interval'  => $this->interval(),
            'byweekday' => $this->byWeekDays(),
        ];
    }

    /**
     * Determine if the given offset exists.
     *
     * @param  string  $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return $this->has($this->getKeyName($offset));
    }

    /**
     * Get the value for a given offset.
     *
     * @param  string  $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->get($this->getKeyName($offset));
    }

    /**
     * Set the value at the given offset.
     *
     * @param  string  $offset
     * @param  mixed  $value
     * @return void
     */
    public function offsetSet($offset, $value): void
    {
        $this->set($offset, $value);
    }

    /**
     * Unset the value at the given offset.
     *
     * @param  string  $offset
     * @return void
     */
    public function offsetUnset($offset): void
    {
        $this->remove($this->getKeyName($offset));
    }

    /**
     * Remove the given key from the attributes.
     *
     * @param  string  $key
     * @return $this
     */
    public function remove(string $key)
    {
        if ($this->has($key)) {
            unset($this->attributes[$key]);
        }

        return $this;
    }

    /**
     * Add or update the given attribute's value.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return $this
     */
    public function set($key, $value)
    {
        $this->attributes[$this->getKeyName($key)] = $value;

        return $this;
    }

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->all();
    }

    /**
     * Convert the instance to JSON.
     *
     * @param  int  $options
     * @return string
     */
    public function toJson($options = 0)
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    /**
     * Get or set the date the recurrence should run until.
     *
     * @param  \Carbon\Carbon|null  $until
     * @return \Carbon\Carbon|$this
     */
    public function until(?Carbon $until = null)
    {
        if (is_null($until)) {
            return $this->get('until');
        }

        return $this->set('until', $until);
    }

    /**
     * Set the recurrence frequency to weekly.
     *
     * @return $this
     */
    public function weekly()
    {
        return $this->frequency(self::WEEKLY);
    }

    /**
     * Get the name of the caster class to use when casting from / to this cast target.
     *
     * @param  array  $arguments
     * @return string
     * @return string|\Illuminate\Contracts\Database\Eloquent\CastsAttributes|\Illuminate\Contracts\Database\Eloquent\CastsInboundAttributes
     */
    public static function castUsing(array $arguments)
    {
        return RecurrenceRuleCast::class;
    }

    /**
     * Allow dynamic access to the attributes.
     *
     * @param  string  $name
     * @return mixed
     */
    public function __get($name)
    {
        return $this->get($name);
    }

    /**
     * Allow dynamic access to setting an attribute's value.
     *
     * @param  string  $name
     * @param  mixed  $value
     * @return void
     */
    public function __set($name, $value)
    {
        return $this->set($name, $value);
    }
}
