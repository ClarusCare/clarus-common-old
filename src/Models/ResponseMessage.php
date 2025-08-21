<?php

namespace ClarusSharedModels\Models;

use ClarusSharedModels\Traits\AttachesS3Files;
use Illuminate\Database\Eloquent\Model as Eloquent;

/**
 * ResponseMessage eloquent model.
 *
 * @author Erik Galloway <egalloway@claruscare.com>
 */
class ResponseMessage extends Eloquent
{
    use AttachesS3Files, PreviousTimestampFormat;

    public static $rules = [];

    /**
     * The attachable S3 file storage definitions.
     *
     * @var array
     */
    protected $attachments = [
        'voice_message' => [
            'storage_path' => 'voice_messages/:id/original',
            'keep_files'   => false,
        ],
    ];

    /**
     * Get the Notification model relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function notification()
    {
        return $this->belongsTo(Notification::class);
    }
}
