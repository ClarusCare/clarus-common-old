<?php

namespace ClarusCommon\Casts;

use ArrayAccess;
use JsonSerializable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Database\Eloquent\Castable;

/**
 * Notification settings castable value object.
 *
 * @author Erik Galloway <egalloway@claruscare.com>
 */
class NotificationSettings implements ArrayAccess, Arrayable, Castable, JsonSerializable, Jsonable
{
    /**
     * The notification settings attributes.
     *
     * @var array
     */
    public $attributes = [];

    /**
     * The attribute keys that should be replaced with their new key.
     *
     * Original key => new key that should replace the original.
     *
     * @var array
     */
    protected $replacements = [
        'schedule_calendar_id' => 'calendar_id',
    ];

    /**
     * Make a new NotificationSettings instance.
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
     * Convert the object into something JSON serializable.
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->all();
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
     * Get the name of the caster class to use when casting from / to this cast target.
     *
     * @param  array  $arguments
     * @return string
     * @return string|\Illuminate\Contracts\Database\Eloquent\CastsAttributes|\Illuminate\Contracts\Database\Eloquent\CastsInboundAttributes
     */
    public static function castUsing(array $arguments)
    {
        return NotificationSettingsCast::class;
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
