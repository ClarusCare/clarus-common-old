<?php

namespace ClarusCommon\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * TranscriptionDriver eloquent model.
 *
 * @author Daniel Picado <dpicado@claruscare.com>
 */
class TranscriptionDriver extends Enumerable
{
    use HasFactory;

    /**
     * Constant representing the manual transcription driver.
     */
    public const AWS = 'aws';

    /**
     * Constant representing the manual transcription driver.
     */
    public const MANUAL = 'manual';

    /**
     * Constant representing the Twilio driver.
     */
    public const TWILIO = 'twilio';

    /**
     * Constant representing the IBM Watson driver.
     */
    public const WATSON = 'watson';

    /**
     * Constant representing the RevAi driver.
     */
    public const REV_AI = 'rev-ai';

    /**
     * Constant representing the whisper driver.
     */
    public const WHISPER = 'whisper';

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
    protected $fillable = ['name', 'label', 'is_automated'];
}
