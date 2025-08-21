<?php

namespace ClarusSharedModels\Models;

use ClarusSharedModels\Traits\AttachesS3Files;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * CallResponse eloquent model.
 *
 * @author Erik Galloway <egalloway@claruscare.com>
 */
class CallResponse extends Model
{
    use AttachesS3Files, HasFactory, PreviousTimestampFormat;

    public static $rules = [];

    /**
     * The attachable S3 file storage definitions.
     *
     * @var array
     */
    protected $attachments = [
        'recording_to_patient' => [
            'storage_path' => 'recording_to_patients/:id/original',
            'keep_files'   => false,
        ],
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'type', 'call_id', 'provider_id', 'text_to_patient', 'user_id'
    ];

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
    public function provider()
    {
        return $this->belongsTo(Provider::class);
    }

    /**
     * Get the User model relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    
    /**
     * Get the provider's full name or the user name if not available.
     *
     * @return string
     */
    public function getProviderNameAttribute()
    {
        $user = null;

        if ($this->provider_id == 0 && $this->user) {
            $user = $this->user;
        } elseif ($this->provider && $this->provider->user) {
            $user = $this->provider->user;
        }

        return $user ? $user->first_name . ' ' . $user->last_name : null;
    }
}
