<?php

namespace ClarusCommon\Models;

use App\Models\TranscriptionType;
use App\Models\TranscriptionDriver;
use App\Transcription\TranscriptionResult;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * AutomatedTranscription eloquent model.
 *
 * @author Daniel Picado <dpicado@claruscare.com>
 */
class AutomatedTranscription extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    /**
     * Constant representing the english language.
     */
    public const ENGLISH = 'en-US';

    /**
     * Constant representing the text to display when the message is inaudible.
     */
    public const INAUDIBLE_MESSAGE = 'Inaudible message.';

    /**
     * Constant representing the languages configuration .
     */
    public const LANGUAGES = [
        'spanish' => [
            'translation' => true,
        ],
        'english' => [
            'translation' => false,
        ],
    ];

    /**
     * Constant representing the maximum confidence level for an automated transcription.
     */
    public const MAX_CONFIDENCE = 1;

    /**
     * Constant representing the spanish language.
     */
    public const SPANISH = 'es-MX';

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'confidence'   => 'double',
        'completed_at' => 'datetime',
        'started_at'   => 'datetime',
        'timed_out_at' => 'datetime',
        'failed_at'    => 'datetime',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'sid',
        'call_id',
        'driver_name',
        'recording_id',
        'status_name',
        'type_name',
        'started_at',
        'completed_at',
        'timed_out_at',
        'failed_at',
        'content',
        'confidence',
        'training',
        'training_content',
        'trained_at',
    ];

    /**
     * Attempt to parse incoming dates.
     *
     * @return float
     */
    protected function parseDOBString(string $inputString = ''): string
    {
        // Remove unecessary puncuation
        $output = str_replace('.', '', $inputString);
        $output = str_replace(',', '', $output);

        try {
            $date = new Carbon($output);
        } catch (\Exception $e) {
            return $inputString;
        }

        return $date->format('m/d/Y');

    }

    /**
     * Attempt to parse incoming messages.
     *
     * @return float
     */
    protected function parseMessagePhone(string $inputText = ''): string
    {
        $potentialPhoneNumbers = array();

        // find phone numbers in the string and put in an array
        preg_match_all('/\+?[0-9][0-9()\-\s+,]{4,30}[0-9]/', $inputText, $potentialPhoneNumbers);

        foreach($potentialPhoneNumbers[0] as $potentialPhoneNumber){

            // remove all spaces and commas in the string
            $combinedText = str_replace(" ", "", $potentialPhoneNumber);
            $combinedText = str_replace(",", "", $combinedText);

            // check the length of the string
            if(strlen($combinedText) == 10){

                // if the string is 10 characters long, continue formatting

                // exract the different elements of the number
                $areaCode = substr($combinedText, 0, 3);
                $nextThree = substr($combinedText, 3, 3);
                $lastFour = substr($combinedText, 6, 4);

                // combine the different elements with formatting
                $formattedPhoneNumber = '('.$areaCode.') '.$nextThree.'-'.$lastFour;

                $inputText = str_replace($potentialPhoneNumber, $formattedPhoneNumber, $inputText);

            }

        }

        return $inputText;

    }
    protected function parseMessageName(string $inputText = ''): string
    {
        $lowercaseNames = array();

        $inputText = str_replace("'", '#apostrophemarker', $inputText);

        // identify all single character words in the string and put them in an array with the character count
        preg_match_all('/(?<![a-z])[a-z]{1,1}+(?=\s|[.][a-z]{1,1}+\s|[.])/', $inputText, $lowercaseNames, PREG_OFFSET_CAPTURE);

        foreach($lowercaseNames[0] as $key => $lowercaseLetter){

            // vars for the next and previous keys in the loop
            $nextLetter = $key + 1;
            $previousLetter = $key - 1;

            if ($previousLetter == -1) {
                $previousLetter = 0;
            }

            // if the current letter is 2 characters away from the next or previous letter, transform the current letter to uppercase
            if ( array_key_exists($nextLetter, $lowercaseNames[0]) ) {

                if (
                    ($lowercaseLetter[1] + 2) == $lowercaseNames[0][$nextLetter][1]
                    || ($lowercaseLetter[1] - 2) == $lowercaseNames[0][$previousLetter][1]
                ) {

                    $inputText = substr_replace($inputText, strtoupper($lowercaseLetter[0]), $lowercaseLetter[1], 1);

                }

            } else {

                if(
                    ($lowercaseLetter[1] - 2) == $lowercaseNames[0][$previousLetter][1]
                ){

                    $inputText = substr_replace($inputText, strtoupper($lowercaseLetter[0]), $lowercaseLetter[1], 1);

                }

            }

        }

        $inputText = str_replace("#apostrophemarker", '\'', $inputText);

        return $inputText;

    }

    /**
     * Get the Call model relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function call()
    {
        return $this->belongsTo(Call::class);
    }

    /**
     * Get the TranscriptionDriver model relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function driver()
    {
        return $this->belongsTo(TranscriptionDriver::class);
    }

    /**
     * Fills the confidence and content based on the given result.
     *
     * @param  \App\Transcription\TranscriptionResult  $result
     * @return $this
     */
    public function fillFromResult(TranscriptionResult $result)
    {
        $this->content = $result->getContent();
        $this->confidence = $result->getConfidence();

        try {

            switch ($this->type['name']) {
                case TranscriptionType::MESSAGE:
                    $this->content = $this->parseMessagePhone($this->content);
                    $this->content = $this->parseMessageName($this->content);
                    break;

                case TranscriptionType::DATE_OF_BIRTH:
                    $this->content = $this->parseDOBString($this->content);
                    break;
            }

        } catch (Exception $e) {

            Log::error(
                "Parsing failed: ",
                ['exception' => $e->getMessage()]
            );

        }

        return $this;
    }

    /**
     * Checks if the transcription is completed.
     *
     * @return bool
     */
    public function isCompleted()
    {
        return ! $this->isNotComplete();
    }

    /**
     * Checks if the confidence level meets the minimum expected.
     *
     * @return bool
     */
    public function isConfident()
    {
        $minimum = $this->driver_name == TranscriptionDriver::WHISPER ? (float) Config::get('open-ai.transcription.min_confidence') : (float) Config::get('transcription.automated.confidence');

        return $this->confidence >= $minimum;
    }

    /**
     * Checks if the confidence level meets the minimum expected.
     *
     * @return bool
     */
    public function isInaudible()
    {
        $minimum = (float) Config::get('transcription.automated.inaudible');

        return $this->confidence <= $minimum;
    }

    /**
     * Check that the transcription is not marked as completed.
     *
     * @return bool
     */
    public function isNotComplete(): bool
    {
        return is_null($this->completed_at);
    }

    /**
     * Checks if the model is timed out.
     *
     * @return bool
     */
    public function isTimedOut()
    {
        return ! is_null($this->timed_out_at);
    }

    /**
     * Checks if thr transcription would need to be translated.
     *
     * @return bool
     */
    public function isTranslatable()
    {
        return ! $this->call->isEnglish();
    }

    /**
     * Mark this model as failed.
     *
     * @return $this
     */
    public function markAsFailed()
    {
        $this->update([
            'failed_at'   => now(),
            'status_name' => TranscriptionStatus::FALLBACK,
        ]);

        return $this;
    }

    /**
     * Mark the model as complete.
     *
     * @return $this
     */
    public function markAsFallback()
    {
        $this->completed_at = now();
        $this->status_name = TranscriptionStatus::FALLBACK;

        $this->save();

        return $this;
    }

    /**
     * Mark the model as timed out.
     *
     * @return $this
     */
    public function markAsTimedOut()
    {
        $this->timed_out_at = now();
        $this->status_name = TranscriptionStatus::FALLBACK;

        $this->save();

        return $this;
    }

    /**
     * Mark the model as complete.
     *
     * @return $this
     */
    public function markAsTranscribed()
    {
        $this->completed_at = now();
        $this->status_name = TranscriptionStatus::TRANSCRIBED;

        $this->save();

        return $this;
    }

    /**
     * Mark this model as transcribing.
     *
     * @return $this
     */
    public function markAsTranscribing()
    {
        $this->started_at = now();
        $this->status_name = TranscriptionStatus::TRANSCRIBING;

        $this->save();

        return $this;
    }

    /**
     * Get the Recording model relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function recording()
    {
        return $this->belongsTo(Recording::class);
    }

    /**
     * Sets the content attribute of the model.
     *
     * @param  string  $value
     * @return void
     */
    public function setContentAttribute($value): void
    {
        $this->attributes['content'] = trim($value) ?: null;
    }

    /**
     * Get the TranscriptionStatus model relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function status()
    {
        return $this->belongsTo(TranscriptionStatus::class);
    }

    /**
     * Get the Transcription model relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function transcription()
    {
        return $this->belongsTo(Transcription::class);
    }

    /**
     * Get the TranscriptionType model relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function type()
    {
        return $this->belongsTo(TranscriptionType::class);
    }

    /**
     * Get the transcription type based on the recording type.
     *
     * @param  \App\Models\Recording  $recording
     * @return string
     */
    public static function getTypeFromRecording($recording)
    {
        $type = $recording->type;

        if ($type === Recording::NAME) {
            return TranscriptionType::NAME;
        }

        if ($type === Recording::MESSAGE) {
            return TranscriptionType::MESSAGE;
        }

        if ($type === Recording::DATE_OF_BIRTH) {
            return TranscriptionType::DATE_OF_BIRTH;
        }
    }
}
