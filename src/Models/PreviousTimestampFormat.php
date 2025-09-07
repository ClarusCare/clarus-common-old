<?php

namespace ClarusCommon\Models;

use DateTimeInterface;

/**
 * Convert timestamps to the previous Laravel timestamp format (Y-m-d h:i:s).
 */
trait PreviousTimestampFormat
{
    /**
     * Prepare a date for array / JSON serialization.
     *
     * @param  \DateTimeInterface  $date
     * @return string
     */
    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }
}
