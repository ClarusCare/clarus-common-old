<?php

namespace ClarusSharedModels\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Recording status eloquent model.
 *
 * @author Erik Galloway <egalloway@claruscare.com>
 */
class RecordingStatus extends Enumerable
{
    use HasFactory;

    /**
     * Constant representing a recording that has been transferred successfully.
     */
    public const COMPLETE = 'complete';

    /**
     * Constant representing a recording that has failed to be transferred.
     */
    public const FAILED = 'failed';

    /**
     * Constant representing a recording that is waiting to be transferred.
     */
    public const PENDING = 'pending';

    /**
     * Constant representing a recording that is currently being transferred.
     */
    public const PROCESSING = 'processing';

    /**
     * Get the Recording model relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function recordings()
    {
        return $this->hasMany(Recording::class);
    }
}
