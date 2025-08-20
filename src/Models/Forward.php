<?php

namespace ClarusSharedModels\Models;

use App\Traits\AttachesS3Files;
use Illuminate\Database\Eloquent\Model as Eloquent;

/**
 * Forward eloquent model.
 *
 * @author Erik Galloway <egalloway@claruscare.com>
 */
class Forward extends Eloquent
{
    use AttachesS3Files, PreviousTimestampFormat;

    public static $rules = [];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['voice_message_url'];

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
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'forwarded_at' => 'datetime',
    ];

    /**
     * User exposed observable events.
     *
     * @var array
     */
    protected $observables = ['forwarded'];

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
     * Get the Provider model relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function forwardFromProvider()
    {
        return $this->belongsTo(Provider::class, 'provider_id');
    }

    /**
     * Get the Provider model relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function forwardToProvider()
    {
        return $this->belongsTo(Provider::class, 'forward_to_provider_id');
    }

    /**
     * Get the voice message URL attribute.
     *
     * @return string|null
     */
    public function getVoiceMessageUrlAttribute()
    {
        if ($this->voice_message_file_name) {
            return url($this->voice_message->url);
        }
    }

    /**
     * Get the Notification model relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function notification()
    {
        return $this->belongsTo(Notification::class);
    }

    /**
     * Save a new Forward instance and fire the forwarded model event.
     *
     * @return void
     */
    public function saveNewForward(): void
    {
        $this->save();

        $this->fireModelEvent('forwarded');
    }

    /**
     * Set the forwarded at attribute.
     *
     * @return void
     */
    public function setForwardedAt(): void
    {
        $this->attributes['forwarded_at'] = $this->freshTimestamp();
    }
}
