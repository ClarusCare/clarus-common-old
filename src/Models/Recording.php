<?php

namespace ClarusSharedModels\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Collections\RecordingCollection;
use App\Http\Middleware\ValidatePresignedUrlSignature;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Log;

class Recording extends Eloquent
{
    use PreviousTimestampFormat, HasFactory;

    /**
     * The constant representing a date of birth recording type.
     */
    public const DATE_OF_BIRTH = 'dob';

    /**
     * The constant representing a message recording type.
     */
    public const MESSAGE = 'message';

    /**
     * Constant representing the minimum valid call recording duration.
     */
    public const MIN_RECORDING_DURATION = 1;

    /**
     * The constant representing a name recording type.
     */
    public const NAME = 'name';

    /**
     * The constant representing an outbound call recording type.
     */
    public const OUTBOUND = 'outbound';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'sid', 'type', 'call_id', 'recording_url',
        'recording_duration', 'exclude_from_transcription', 'status_name',
        'trained_at',
        'training_content',
        'for_training',
        'transcription_language',
    ];

    protected $hidden = ['id', 'call_id', 'updated_at'];

    protected $casts = [
        'trained_at' => 'datetime',
    ];

    /**
     * Should the secure temporary URL be used in place of the recording url attribute.
     *
     * @var bool
     */
    protected $useSecureUrl = false;

    public static $twilioToTranscriptionLanguageMap = [
        'en-US' => 'en',
        'es-MX' => 'es',
    ];

    public function allForCall($callID)
    {
        // Query for recordings in order of name, dob, message
        return self::where('call_id', $callID)
            ->orderBy(DB::raw('CASE type WHEN \'name\' THEN 1 WHEN \'dob\' THEN 2 WHEN \'message\' THEN 3 ELSE 4 END'))
            ->get();
    }

    /**
     * Get the AutomatedTranscription model relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function automatedTranscription()
    {
        return $this->hasOne(AutomatedTranscription::class);
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
     * Mark the recording file transfer status as complete.
     *
     * @return $this
     */
    public function complete()
    {
        $this->status_name = RecordingStatus::COMPLETE;

        return $this;
    }

    /**
     * Mark the recording file transfer status as failed.
     *
     * @return $this
     */
    public function failed()
    {
        $this->status_name = RecordingStatus::FAILED;

        return $this;
    }

    /**
     * Determine if the recording's audio file exists in storage.
     *
     * @param  string|null  $disk
     * @return bool
     */
    public function fileExists($disk = null)
    {
        return Storage::disk($disk)->exists($this->getS3FilePath());
    }

    /**
     * Get the recording's file name used in permanent storage.
     *
     * @param  string  $extension
     * @return string
     */
    public function getFileName($extension = 'wav'): string
    {
        return "{$this->type}.{$extension}";
    }

    /**
     * Get the base file path of the audio file in permanent storage.
     *
     * @return string
     */
    public function getFilePath(): string
    {
        return "recordings/calls/{$this->call_id}";
    }

    /**
     * Append .mp3 to url string when no extension exists.
     *
     * @return string
     */
    public function getMp3RecordingUrlAttribute()
    {
        $url = $this->recording_url;

        if (preg_match('/.mp3$/', $url, $mp3Matches) || preg_match('/.wav$/', $url, $wavMatches)) {
            return $url;
        }

        return $url.'.mp3';
    }

    /**
     * Get the S3 storage file path.
     *
     * @param  string  $extension
     * @return string
     */
    public function getS3FilePath($extension = 'wav'): string
    {
        $path = $this->getFilePath();

        $fileName = $this->getFileName($extension);

        return "{$path}/{$fileName}";
    }

    /**
     * Get the secure URL attribute for the recording.
     *
     * @return string
     */
    public function getSecureUrlAttribute()
    {
        return $this->recording_url;
    }

    /**
     * Get the full AWS S3 URL for the audio file.
     *
     * @param  string|null  $disk
     * @return string
     */
    public function getStorageUrl($disk = null)
    {
        return Storage::disk($disk)->url($this->getS3FilePath());
    }

    /**
     * Determine if the recording's audio file transfer has failed.
     *
     * @return bool
     */
    public function hasFailed(): bool
    {
        return $this->status_name === RecordingStatus::FAILED;
    }

    /**
     * Checks if there's a message in the recording.
     *
     * @return bool
     */
    public function hasMessage()
    {
        return ((int) $this->recording_duration) >= static::MIN_RECORDING_DURATION;
    }

    /**
     * Determine if a recording URL exists.
     *
     * @return bool
     */
    public function hasRecordingUrl(): bool
    {
        if (! array_key_exists('recording_url', $this->attributes)) {
            return false;
        }

        return (bool) $this->attributes['recording_url'];
    }

    /**
     * Determine if the recording's audio file transfer has completed.
     *
     * @return bool
     */
    public function isComplete(): bool
    {
        return $this->status_name === RecordingStatus::COMPLETE;
    }

    /**
     * Determine if the recording's audio file transfer is pending.
     *
     * @return bool
     */
    public function isPending(): bool
    {
        return $this->status_name === RecordingStatus::PENDING;
    }

    /**
     * Determine if the recording's audio file transfer is processing.
     *
     * @return bool
     */
    public function isProcessing(): bool
    {
        return $this->status_name === RecordingStatus::PROCESSING;
    }

    /**
     * Create a new Eloquent Collection instance.
     *
     * @param  array  $models
     * @return \App\Collections\RecordingCollection
     */
    public function newCollection(array $models = [])
    {
        return new RecordingCollection($models);
    }

    /**
     * Mark the recording file transfer status as pending.
     *
     * @return $this
     */
    public function pending()
    {
        $this->status_name = RecordingStatus::PENDING;

        return $this;
    }

    /**
     * Mark the recording file transfer status as processing.
     *
     * @return $this
     */
    public function processing()
    {
        $this->status_name = RecordingStatus::PROCESSING;

        return $this;
    }

    /**
     * Get the RecordingStatus model relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function status()
    {
        return $this->belongsTo(RecordingStatus::class);
    }

    /**
     * Get the Transcription model relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function transcription()
    {
        return $this->hasOne(Transcription::class);
    }

    /**
     * Use the temporary secure URL in place of the recording url attribute.
     *
     * @param  bool  $secure
     * @return $this
     */
    public function useSecureUrl($secure = false)
    {
        $this->useSecureUrl = $secure;

        return $this;
    }

    public function getRecordingUrlAttribute($value)
    {
        try {
            if (config('filesystems.disks.s3_private.is_private_bucket') && self::isComplete()) {
                if ($this->fileExists('s3_private')) {
                    Log::info("S3 file exists, generating presingned url, recording id : $this->id");
                    $validateUrlInstance = new ValidatePresignedUrlSignature();
                    $url = config('app.url')."/api/v4/resource-url?token=".$validateUrlInstance->generateHashKey("recordings$this->id")."&type=recordings&identifier=$this->id";
                } else {
                    Log::info("TWILIO_RECORDING: S3 file does not exist, fetching recording from twilio, recording id : $this->id");
                    $url = $value;
                }
            } else {
                if (str_contains($value, 'twilio')) {
                    Log::info("TWILIO_RECORDING: Fetch recording from twilio, recording id : $this->id");
                }
                if (str_starts_with($this->sid, 'MRE')) {
                    Log::info("Manually created recordings, recording id : $this->id");
                }
                $url = $value;
            }
            return $url;
        } catch (\RuntimeException $e) {
            Log::info("An unexpected error occurred while creating the temporary URL.");
        }
        return $value;
    }

    public function setTranscriptionLanguageAttribute($value) {
        // Check if $value present and there's a mapping for the $value(language)
        if (!empty($value) && isset(self::$twilioToTranscriptionLanguageMap[$value])) {
            $this->attributes['transcription_language'] = self::$twilioToTranscriptionLanguageMap[$value];
        } else {
            // If there's no mapping, return the default language
            $this->attributes['transcription_language'] = 'en';
        }
    }

    /**
     * get the recording_url column value by recording id 
     *
     * @param  int  $id
     * @return string recording_url
     */
    public function getRecordingUrlById($id)
    {
        $result = Recording:: select('recording_url')->where('id', $id)->first();
        if ($result === null) {
            return null;
        } else {
            return $result->recording_url;
        }
    }
}
