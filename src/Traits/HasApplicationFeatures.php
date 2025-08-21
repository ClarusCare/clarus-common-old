<?php

namespace ClarusSharedModels\Traits;

use ClarusSharedModels\Models\Feature;
use ClarusSharedModels\Models\Application;
use ClarusSharedModels\Models\ApplicationPartner;
use Illuminate\Database\Eloquent\Model;
use App\Database\Relations\FeatureRelation;

/**
 * Application features trait.
 *
 * @author Daniel Picado <dpicado@claruscare.com>
 */
trait HasApplicationFeatures
{
    /**
     * Get the ApplicationPartner model relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function applicationPartners()
    {
        return $this->hasMany(ApplicationPartner::class);
    }

    /**
     * Get the Application model relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function applications()
    {
        return $this->belongsToMany(Application::class)
            ->withPivot('id')
            ->withTimestamps()
            ->using(ApplicationPartner::class);
    }

    /**
     * Get the Feature model relationship.
     *
     * @return \App\Database\Relations\FeatureRelation
     */
    public function features()
    {
        return new FeatureRelation($this);
    }

    /**
     * Check if the model has a specific application feature.
     *
     * @param  int  $applicationId
     * @param  int  $featureId
     * @return bool
     */
    public function hasAppFeature($applicationId, $featureId)
    {
        return $this->applicationPartners()->where('application_id', $applicationId)->whereHas('features', fn ($query) => $query->where('id', $featureId))->exists();
    }

    /**
     * Check if the model has transcription feature enabled for the app.
     *
     * @return bool
     */
    public function hasAvailableForPatients()
    {
        return $this->hasFeature(Feature::AVAILABLE_FOR_PATIENTS);
    }

    /**
     * Check if the model has transcription feature enabled for the app.
     *
     * @return bool
     */
    public function hasCallRecording()
    {
        return $this->hasFeature(Feature::RECORD_CALLS);
    }

    /**
     * Check if the model has transcription feature enabled for the app.
     *
     * @return bool
     */
    public function hasChat()
    {
        return $this->hasFeature(Feature::CHAT);
    }

    /**
     * Check if the model has transcription feature enabled for the app.
     *
     * @return bool
     */
    public function hasCoverMyCall()
    {
        return $this->hasFeature(Feature::COVER_MY_CALL);
    }

    /**
     * Check if the model has the feature on the given app.
     *
     * @param  \App\Models\Feature|string|int  $feature
     * @return bool
     */
    public function hasFeature($feature): bool
    {
        if ($feature instanceof Feature) {
            $feature = $feature->name;
        }

        if ($this->relationLoaded('features')) {
            return $this->features->contains(function ($model) use ($feature) {
                return is_string($feature)
                    ? $model->name === $feature
                    : $model->id === $feature;
            });
        }

        if (is_string($feature)) {
            return $this->features()->whereName($feature)->exists();
        }

        return $this->features()->find((int) $feature);
    }

    /**
     * Check if the model has messaging feature enabled for the app.
     *
     * @return bool
     */
    public function hasMessaging()
    {
        return $this->hasFeature(Feature::MESSAGING);
    }

    /**
     * Check if the model has transcription feature enabled for the app.
     *
     * @return bool
     */
    public function hasPaging()
    {
        return $this->hasFeature(Feature::PAGING);
    }

    /**
     * Check if the model has transcription feature enabled for the app.
     *
     * @return bool
     */
    public function hasTranscription()
    {
        return $this->hasFeature(Feature::TRANSCRIPTION);
    }

    /**
     * Check if the model has webforms feature enabled for the app.
     *
     * @return bool
     */
    public function hasWebforms()
    {
        return $this->hasFeature(Feature::WEBFORMS);
    }
    
    /**
     * Check if the model has manual transcription feature enabled for the app.
     *
     * @return bool
     */
    public function hasManualTranscription()
    {
        return $this->hasFeature(Feature::MANUAL_TRANSCRIPTION);
    }

    /**
     * Sync the features on each application.
     *
     * @param  array  $applications
     * @param  array  $features
     * @return void
     */
    public function syncFeatures($applications, $features): void
    {
        $this->applicationPartners()->whereNotIn('application_id', $applications ?? [])->delete();

        foreach ($applications as $app) {
            $applicationPartner = $this->applicationPartners()->firstOrCreate(['application_id' => $app]);

            $applicationPartner->features()->sync(array_keys($features[$app] ?? []));
        }
    }
}
