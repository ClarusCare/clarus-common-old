<?php

namespace ClarusSharedModels\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * TranscriptionType eloquent model.
 *
 * @author Daniel Picado <dpicado@claruscare.com>
 */
class TranscriptionType extends Enumerable
{
    use HasFactory;

    /**
     * The constant representing the date of birth transcription type.
     */
    public const DATE_OF_BIRTH = 'date-of-birth';

    /**
     * The constants representing the max length of each type.
     *
     * @var array
     */
    public const MAX_LENGTHS = [
        'date-of-birth' => 250,
        'message'       => 16383,
        'name'          => 250,
    ];

    /**
     * The constant representing the message transcription type.
     */
    public const MESSAGE = 'message';

    /**
     * The constant representing the name transcription type.
     */
    public const NAME = 'name';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['name', 'label'];

    /**
     * Determine if the transcription is a date of birth transcription.
     *
     * @return bool
     */
    public function isDateOfBirth(): bool
    {
        return $this->name === static::DATE_OF_BIRTH;
    }

    /**
     * Determine if the transcription is a message transcription.
     *
     * @return bool
     */
    public function isMessage(): bool
    {
        return $this->name === static::MESSAGE;
    }

    /**
     * Determine if the transcription is a name transcription.
     *
     * @return bool
     */
    public function isName(): bool
    {
        return $this->name === static::NAME;
    }
}
