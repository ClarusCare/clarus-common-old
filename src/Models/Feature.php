<?php

namespace ClarusSharedModels\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Feature eloquent model.
 *
 * @author Erik Galloway <egalloway@claruscare.com>
 */
class Feature extends Model
{
    use HasFactory;

    /**
     * The constant representing 'available for patients' feature.
     */
    public const AVAILABLE_FOR_PATIENTS = 'available-for-patients';

    /**
     * The constant representing 'chat' feature.
     */
    public const CHAT = 'chat';

    /**
     * The constant representing 'cover my call' feature.
     */
    public const COVER_MY_CALL = 'cover-my-call';

    /**
     * The constant representing the 'messaging' feature.
     */
    public const MESSAGING = 'messaging';

    /**
     * The constant representing 'paging' feature.
     */
    public const PAGING = 'paging';

    /**
     * The constant representing 'record patient calls' feature.
     */
    public const RECORD_CALLS = 'record-patient-calls';

    /**
     * The constant representing 'transcription' feature.
     */
    public const TRANSCRIPTION = 'transcription';
    
    /**
     * The constant representing 'manual transcription' feature.
     */
    public const MANUAL_TRANSCRIPTION = 'manual-transcription';

    /**
     * The constant representing 'webforms' feature.
     */
    public const WEBFORMS = 'webforms';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['name', 'label', 'description'];

    /**
     * Get the ApplicationPartner model relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function applicationPartners()
    {
        return $this->belongsToMany(
            ApplicationPartner::class,
            'application_partner_feature',
            'feature_id',
            'application_partner_id'
        );
    }

    /**
     * Get the Application model relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function applications()
    {
        return $this->belongsToMany(Application::class, 'application_feature')
            ->using(ApplicationFeature::class)
            ->withTimestamps();
    }
}
