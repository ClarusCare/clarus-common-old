<?php

namespace ClarusSharedModels\Models;

use App\Traits\AttachesS3Files;
use Illuminate\Database\Eloquent\Model as Eloquent;

/**
 * TwilioScript eloquent model.
 *
 * @author Erik Galloway <egalloway@claruscare.com>
 */
class TwilioScript extends Eloquent
{
    use AttachesS3Files, PreviousTimestampFormat;

    public static $languages = [
        'en-US' => 'English, Male',
        'es-MX' => 'Spanish, Male',
    ];

    public static $updateLanguageCode = [
        'en-AU' => 'en-US',
        'en-CA' => 'en-US',
        'en-GB' => 'en-US',
        'en-IN' => 'en-US',
        'en-US' => 'en-US',
        'es-ES' => 'es-MX',
        'es-MX' => 'es-MX',
    ];

    public static $rules = [
        'value'              => 'required_if:format,text-to-speech|max:4000',
        'language'           => 'required',
        'format'             => 'required',
        'audio'              => 'required_if:format,audio',
        'audio.content_type' => 'regex:/audio\/wav/',
    ];

    public $scriptNames = [
        'WELCOME',
        'MAIN_OPTIONS',
        'INVALID_OPTION',
        'VERIFY_PHONE_1',
        'VERIFY_PHONE_2',
        'VERIFY_PHONE_3',
        'GATHER_NAME',
        'GATHER_DOB',
        'GATHER_MD',
        'GATHER_MESSAGE_STANDARD',
        'GATHER_MESSAGE_URGENT',
        'GATHER_PROVIDER_START',
        'WRAP',
        'OB_MESSAGE_WELCOME',
        'OB_PROVIDER_WELCOME',
        'VOICEMAIL_OPTION',
        'PATIENT_URGENT_OPTION',
        'PROVIDER_URGENT_OPTION',
        'GATHER_MESSAGE_PROVIDER',
        'GATHER_PROVIDER_START_ALT',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['audio_url'];

    /**
     * The attachable S3 file storage definitions.
     *
     * @var array
     */
    protected $attachments = [
        'audio' => [
            'storage_path' => 'audios/:id/original',
            'keep_files'   => false,
        ],
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'language', 'value', 'partner_id', 'voice', 'name', 'format', 'audio_file_name', 'audio_file_size', 'audio_content_type'
    ];

    public function createRules($partnerID)
    {
        return [
            'name'               => 'required|unique:twilio_scripts,name,NULL,id,partner_id,'.$partnerID,
            'value'              => 'required_if:format,text-to-speech|max:4000',
            'language'           => 'required',
            'format'             => 'required',
            'audio'              => 'required_if:format,audio',
            'audio.content_type' => 'regex:/audio\/wav/',
        ];
    }

    /**
     * Get the audio url attribute.
     *
     * @return string|void
     */
    public function getAudioUrlAttribute()
    {
        if (isset($this->attributes['audio_file_name'])) {
            return $this->attachmentUrl('audio');
        }
    }

    /**
     * Get the script names attribute.
     *
     * @return array
     */
    public function getScriptNamesAttribute()
    {
        return $this->scriptNames;
    }

    /**
     * Get the IvrModule model relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function ivrModules()
    {
        return $this->hasMany(IvrModule::class);
    }

    /**
     * Get the Partner model relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function partner()
    {
        return $this->belongsTo(Partner::class);
    }

    public function updateRules($partnerID, $script)
    {
        return [
            'name'               => 'required|unique:twilio_scripts,name,'.$this->attributes['id'].',id,partner_id,'.$partnerID,
            'value'              => 'required_if:format,text-to-speech|max:4000',
            'language'           => 'required',
            'format'             => 'required',
            'audio'              => 'required_if:format,audio',
            'audio.content_type' => 'regex:/audio\/wav/',
        ];
    }

    public function getLanguageAttribute($value)
    {
        // Check if there's a mapping for the $value(language)
        if (isset(self::$updateLanguageCode[$value])) {
            return self::$updateLanguageCode[$value];
        } else {
            // If there's no mapping, return the default language
            return 'en-US';
        }
    }
}
