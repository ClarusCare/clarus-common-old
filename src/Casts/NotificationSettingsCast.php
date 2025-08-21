<?php

namespace ClarusSharedModels\Casts;

use Illuminate\Database\Eloquent\JsonEncodingException;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

/**
 * Notification settings cast.
 *
 * @author Erik Galloway <egalloway@claruscare.com>
 */
class NotificationSettingsCast implements CastsAttributes
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

        return new NotificationSettings((array) $value);
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
         * this is being called because the notification_settings
         * property is being set to a string (JSON) directly on the model.
         *
         * Example: $call->notification_settings = json_encode([...]);
         * Example: $call->update(['notification_settings' => json_encode([...])]);
         */
        if (is_string($value)) {
            $value = json_decode($value, true);
        }

        /*
         * If trying to set the value of notification settings to an array
         * directly, then we need to create a new value object first and
         * then convert that value to JSON.
         *
         * Example: $call->notification_settings = [...];
         * Example: $call->update(['notification_settings' => [...]]);
         */
        if (is_array($value)) {
            $value = new NotificationSettings($value);
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
