<?php

namespace ClarusCommon\Casts;

use Illuminate\Database\Eloquent\JsonEncodingException;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

/**
 * Recurrence rule cast.
 *
 * @author Erik Galloway <egalloway@claruscare.com>
 */
class RecurrenceRuleCast implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $key
     * @param  mixed  $value
     * @param  array  $attributes
     * @return mixed
     */
    public function get($model, $key, $value, $attributes)
    {
        if (is_string($value)) {
            $value = json_decode($value, true);
        }

        return new RecurrenceRule((array) $value);
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $key
     * @param  mixed  $value
     * @param  array  $attributes
     * @return mixed
     */
    public function set($model, $key, $value, $attributes)
    {
        /*
        * If the value is a string, then it is most likely JSON and
        * this is being called because the recurrence property is
        * being set to a string (JSON) directly on the model.
        *
        * Example: $event->recurrence = json_encode([...]);
        * Example: $event->update(['recurrence' => json_encode([...])]);
        */
        if (is_string($value)) {
            $value = json_decode($value, true);
        }

        /*
         * If trying to set the value of the recurrence rule as an array
         * directly, then we need to create a new value object first and
         * then convert that value to JSON.
         *
         * Example: $event->recurrence = [...];
         * Example: $event->update(['recurrence' => [...]]);
         */
        if (is_array($value)) {
            $value = new RecurrenceRule($value);
        }

        if (! $value->any()) {
            return;
        }

        $json = $value->toJson();

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw JsonEncodingException::forAttribute($model, 'notification_settings', json_last_error_msg());
        }

        return $json;
    }
}
