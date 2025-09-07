<?php

namespace ClarusCommon\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * Transcription eloquent model.
 *
 * @author Daniel Picado <dpicado@claruscare.com>
 */
class Transcription extends Eloquent implements Auditable
{
    use HasFactory;
    use PreviousTimestampFormat;
    use \OwenIt\Auditing\Auditable;

    /**
     * The model's attributes.
     *
     * @var array
     */
    protected $attributes = [
        'status'         => 'in-progress',
        'auto_generated' => false,
        'status_name'    => TranscriptionStatus::PENDING,
        'driver_name'    => TranscriptionDriver::MANUAL,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        /*
         * The text attribute needs to remain fillable for backwards compatibility.
         * Anytime the text attribute is set, it will call the setTextAttribute
         * mutator method that sets the new "content" attribute/column instead.
         */
        'text',
        'sid',
        'auto_generated',
        'recording_id',
        'status',
        'content',
        'status_name',
        'driver_name',
        'type_name',
        'automated_transcription_id',
    ];

    /**
     * Get the AutomatedTranscription model relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function automatedTranscription()
    {
        return $this->belongsTo(AutomatedTranscription::class);
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
     * Get a manually generated transcription sid.
     *
     * @return string
     */
    public function getManuallyGeneratedTranscriptionSid()
    {
        return (string) Str::uuid();
    }

    /**
     * Alias for retrieving the transcription's content attribute.
     *
     * @return string
     */
    public function getTextAttribute()
    {
        return $this->content;
    }

    /**
     * Determine if the transcription was made by an automated service.
     *
     * @return bool
     */
    public function isAutomatedTranscription(): bool
    {
        return ! is_null($this->automated_transcription_id);
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
     * Sets the status attribute.
     *
     * @param  string  $value
     * @return void
     */
    public function setStatusAttribute($value): void
    {
        if ($value === 'in-progress') {
            $this->status_name = TranscriptionStatus::PENDING;
        }

        if (in_array($value, ['completed', 'complete'])) {
            $this->setCompletedStatus();
        }

        $this->attributes['status'] = $value;
    }

    /**
     * Setter for the text attribute.
     *
     * @param  string  $value
     * @return void
     */
    public function setTextAttribute($value): void
    {
        $this->attributes['content'] = $value;
    }

    /**
     * Sets the type based on the recording.
     *
     * @return $this
     */
    public function setTypeFromRecording()
    {
        if (! is_null($this->type_name)) {
            return $this;
        }

        $recordingType = optional($this->recording)->type;

        switch ($recordingType) {
            case 'name':
                $this->type_name = TranscriptionType::NAME;

                break;

            case 'dob':
                $this->type_name = TranscriptionType::DATE_OF_BIRTH;

                break;

            case 'message':
                $this->type_name = TranscriptionType::MESSAGE;

                break;
        }

        return $this;
    }

    /**
     * Get the TranscriptionStatus model relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function transcriptionStatus()
    {
        return $this->belongsTo(TranscriptionStatus::class, 'status_name');
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
     * Create a new model based on the given automated transcription.
     *
     * @param  \App\Models\AutomatedTranscription  $automated
     * @return \App\Models\Transcription
     */
    public static function createFromAutomated($automated)
    {
        return static::create([
            'auto_generated'             => true,
            'status'                     => 'complete',
            'sid'                        => $automated->sid,
            'recording_id'               => $automated->recording_id,
            'content'                    => $automated->content,
            'status_name'                => $automated->status_name,
            'driver_name'                => $automated->driver_name,
            'type_name'                  => $automated->type_name,
            'automated_transcription_id' => $automated->id,
        ]);
    }

    /**
     * Set the proper completed status depending on the auto_generated value.
     *
     * @return $this
     */
    protected function setCompletedStatus()
    {
        if ($this->isAutomatedTranscription()) {
            $this->status_name = TranscriptionStatus::TRANSCRIBED;

            return $this;
        }

        $this->status_name = TranscriptionStatus::COMPLETE;

        return $this;
    }

    /**
     * Perform any actions required after the model boots.
     *
     * @return void
     */
    protected static function booted(): void
    {
        static::saving(function ($model): void {
            $model->setTypeFromRecording();
        });
    }
}
