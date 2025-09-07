<?php

namespace ClarusCommon\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * TranscriptionStatus eloquent model.
 *
 * @author Daniel Picado <dpicado@claruscare.com>
 */
class TranscriptionStatus extends Enumerable
{
    use HasFactory;

    /**
     * The constant representing transcription complete status;.
     */
    public const COMPLETE = 'complete';

    /**
     * The constant representing the failed transcription status.
     */
    public const FAILED = 'failed';

    /**
     * The constant representing the transcription fallback status.
     */
    public const FALLBACK = 'fallback';

    /**
     * The constant representing transcription pending status;.
     */
    public const PENDING = 'pending';

    /**
     * The constant representing transcribed status.
     */
    public const TRANSCRIBED = 'transcribed';

    /**
     * The constant representing transcribing status;.
     */
    public const TRANSCRIBING = 'transcribing';

    /**
     * The constant representing the waiting transcription status.
     */
    public const WAITING = 'waiting';

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'is_automated' => 'boolean',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['name', 'label', 'is_automated', 'description'];
}
