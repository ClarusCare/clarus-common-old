<?php

namespace ClarusSharedModels\Models;

use Carbon\Carbon;
use NumberFormatter;
use ClarusSharedModels\Traits\Deferrable;
use ClarusSharedModels\Casts\NotificationSettings;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
use App\Factories\CallNotificationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use ClarusSharedModels\Models\PreviousTimestampFormat;

class Call extends Model
{
    use Deferrable;
    use HasFactory;
    use PreviousTimestampFormat;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * Constant representing the call statuses of a notifiable call.
     *
     * @constant array
     */
    public const NOTIFICATION_SENT_STATUSES = [
        'missed',
        'notifications-sent',
        'complete',
        'responded',
        'in-progress',
        'dismiss',
    ];

    public static $notificationSchemes = [
        'no_notifications'         => 'No Notifications',
        'primary_provider_first'   => 'Primary Provider First',
        'secondary_provider_first' => 'Secondary Provider First',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'completed_by', 'completed_action', 'wma_allow_reply'
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array
     */
    protected $attributes = [
        'language'  => 'en-US',
        'is_urgent' => false,
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'duration'              => 'int',
        'completed_at'          => 'datetime',
        'transcribed_at'        => 'datetime',
        'archived_at'           => 'datetime',
        'ended_at'              => 'datetime',
        'notification_settings' => NotificationSettings::class,
    ];

    /**
     * The attributes that aren't mass assignable.
     *
     * @var string[]|bool
     */

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'status',
        'last_notification_id',
        'last_notified_provider_id',
        'providers_attempted',
        'notifications_attempted',
        'direction',
        'notification_settings',
        'completionResponse',
    ];

    protected $presenter = \App\Presenters\CallPresenter::class;

    public function archive(): void
    {
        if ($this->completed_at && $this->archived_at === null) {
            $this->status = 'archived';
            $this->archived_at = Carbon::now();
            $this->save();
        }
    }

    /**
     * Get the AutomatedTranscription model relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function automatedTranscriptions()
    {
        return $this->hasMany(AutomatedTranscription::class);
    }

    public function callData()
    {
        return $this->hasMany(CallDatum::class);
    }

    public function callEvents()
    {
        return $this->hasMany(CallEvent::class);
    }

    public function callPatientDetails()
    {
        return $this->hasOne(CallPatientDetails::class);
    }

    public function partnerCallAttributeValues()
    {
        return $this->hasMany(PartnerCallAttributeValue::class, 'call_id')
            ->whereHas('partnerCallAttribute', function ($query) {
                $query->where('active', 1);
            });
    }
    
    /**
     * Get call attribute values by internal name for the current call
     * 
     * Retrieves partner call attribute values filtered by attribute name(s).
     * The call_id filtering is automatically applied through the relationship.
     * Returns all attribute values if no name filter is provided.
     *
     * @param string|array|null $attributeName Internal name(s) of attribute(s) to retrieve
     * @return \Illuminate\Database\Eloquent\Collection Collection of PartnerCallAttributeValue models
     */
    public function getCallAttributeValues($attributeName = null)
    {
        $query = $this->partnerCallAttributeValues()
            ->with('partnerCallAttribute.callAttribute');
            
        if ($attributeName) {
            $query->whereHas('partnerCallAttribute.callAttribute', function($q) use ($attributeName) {
                if (is_array($attributeName)) {
                    $q->whereIn('internal_name', $attributeName);
                } else {
                    $q->where('internal_name', $attributeName);
                }
            });
        }
        
        return $query->get();
    }

    /**
     * Get the Call Automated Transcription status model relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function callAutomatedTranscriptionStatus()
    {
        return $this->hasOne(CallAutomatedTranscriptionStatus::class);
    }

    public function callNotes()
    {
        return $this->hasMany(CallNote::class);
    }

    public function callResponses()
    {
        return $this->hasMany(CallResponse::class);
    }

    public function callTypes()
    {
        return $this->belongsToMany(CallType::class, 'call_call_type', 'call_id', 'call_type_id');
    }

    public function completedByProvider()
    {
        return $this->belongsTo(Provider::class, 'completed_by_provider_id');
    }

    public function completedByUser()
    {
        return $this->belongsTo(User::class, 'completed_by_user_id');
    }

    public function dismissedBy()
    {
        return $this->belongsTo(User::class, 'dismissed_by');
    }

    public function completionResponse()
    {
        return $this->belongsTo(CallResponse::class, 'completion_response_id');
    }

    public function defer(): void
    {
        $notification = $this->latestNotification;
        $notification->response = 'defer';
        $notification->status = 'defer';
        $notification->save();

        //SEND TO THE NEXT PROVIDER
    }

    public function dismiss(): void
    {
        if ($notification = $this->latestNotification) {
            $notification->response = 'dismiss';
            $notification->save();

            $notification->resolve();
        }

        $this->resolve();
    }

    public function findTranscriptionByType($type)
    {
        foreach ($this->recordings as $recording) {
            if ($recording->transcription && $recording->type == $type) {
                return $recording->transcription;
            }
        }
    }

    public function forward($forwardToProviderId, $voiceMessage, $message)
    {
        $forward = App::make(Forward::class);
        $forward->call_id = $this->id;
        $forward->provider_id = $this->last_notified_provider_id;
        Log::info("Call forwarded to : $forwardToProviderId");
        if (is_numeric($forwardToProviderId) && $forwardToProviderId != 'office_staff') {
            $forward->forward_to_provider_id = $forwardToProviderId;
        }
        $forward->message = $message;
        $forward->setForwardedAt();

        if ($voiceMessage) {
            $forward->voice_message = $voiceMessage;
        }

        $forward->saveNewForward();

        return $forward;
    }

    /**
     * Get the Forward model relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function forwards()
    {
        return $this->hasMany(Forward::class);
    }

    public function getCompletedActionAttribute()
    {
        if ($response = $this->completionResponse) {
            return $response->type;
        } else if ($this->dismissedBy) {
            return $this->status;
        }
        return 'N/A';
    }

    /**
     * Get the completed by attribute.
     *
     * @return string|null
     */
    public function getCompletedByAttribute()
    {
        if (is_null($this->completed_at)) {
            return;
        }

        if ($this->completedByUser) {
            return $this->completedByUser->full_name;
        }

        if ($this->completedByProvider) {
            return $this->completedByProvider->name;
        }

        if ($this->latestProvider) {
            return $this->latestProvider->name;
        }
    }

    /**
     * Get the dismissed by attribute.
     *
     * @return string|null
     */
    public function getDismissedByUserAttribute($value)
    {
        if ($this->dismissedBy) {
            return $this->dismissedBy->full_name;
        }
        return;
    }

    public function getOrdinalAttemptedNotifications()
    {
        $formatter = new NumberFormatter('en', NumberFormatter::ORDINAL);

        return $formatter->format($this->notifications_attempted);
    }

    /**
     * Get the language attribute.
     *
     * @return string|null
     */
    public function getRecordingLanguageAttribute()
    {
        return $this->language === 'en-US'
            ? AutomatedTranscription::ENGLISH
            : AutomatedTranscription::SPANISH;
    }

    /**
     * Check if all automated transcriptions for a call are completed.
     *
     * This method retrieves the transcription status and compares the total
     * recording count with the total transcription count. If the counts match,
     * it indicates that all transcriptions are completed.
     *
     * @return bool Returns true if all transcriptions are completed, otherwise false.
     */
    public function automatedTranscriptionsCompleted() : bool
    {
        // Retrieve the transcription status
        $status = $this->callAutomatedTranscriptionStatus;

        // Return false if the status is not set (null)
        if ($status === null) {
            return false;
        }

        // Compare total recording count with total transcription count
        return $status->total_recording_count === $status->total_transcription_count;
    }

    /**
     * Update the automatic transcription status for a given Call.
     *
     * This method increments the `total_transcription_count` by 1 for the associated
     * `CallAutomatedTranscriptionStatus` of the given Call. If no status exists, it
     * creates a new status record with the initial `total_transcription_count` set to 1
     * and the `total_recording_count` set to the count of the Call's recordings.
     *
     * @return void
     */
    public function updateAutomaticTranscriptionStatus() : void
    {
        //Check if the Call has an associated CallAutomatedTranscriptionStatus
        if ($status = $this->callAutomatedTranscriptionStatus) {
            // Increment the total_transcription_count by 1
            $status->increment('total_transcription_count');
        } else {
            // Handle cases where there is no associated CallAutomatedTranscriptionStatus record
            // Optionally create a new status record if needed
            $attributes = [
                'total_recording_count' => $this->recordings()->count(), // Adjust as necessary
                'total_transcription_count' => 1,
            ];
            $this->callAutomatedTranscriptionStatus()->create($attributes);
        }
    }

    /**
     * This method is used only for RevAi Transcriptions.
     * Increments the total transcription count for the associated automated transcription status.
     *
     * This method checks if the call has an associated automated transcription status.
     * If it exists, it increments the 'total_transcription_count' field by one.
     *
     * @return void
     */
    public function incrementTranscriptionCount(): void
    {
        $status = $this->callAutomatedTranscriptionStatus;

        if ($status) {
            $status->increment('total_transcription_count');
        }
    }


    /**
     * Determine if the call status is "new.".
     *
     * @return bool
     */
    public function hasNewStatus(): bool
    {
        return $this->status === 'new';
    }

    public function inProgress(): void
    {
        if ($this->isNotCompleted()) {
            $this->status = 'in-progress';
            $this->save();
        }
    }

    /**
     * Verify if the call was in english language.
     *
     * @return bool
     */
    public function isEnglish()
    {
        return $this->recording_language === AutomatedTranscription::ENGLISH;
    }

    /**
     * Determine if the call is not completed.
     *
     * @return bool
     */
    public function isNotCompleted(): bool
    {
        return is_null($this->completed_at);
    }

    /**
     * Determine if the call is not dismissed.
     *
     * @return bool
     */
    public function isNotDismissed(): bool
    {
        return is_null($this->dismissed_by);
    }

    /**
     * Determine if the call status is dismiss.
     *
     * @return bool
     */
    public function isDismissed(): bool
    {
        return $this->status === 'dismiss';
    }

    /**
     * Determine if the call is not in progress.
     *
     * @return bool
     */
    public function isNotInProgress(): bool
    {
        return $this->status !== 'in-progress';
    }

    public function latestNotification()
    {
        return $this->belongsTo(Notification::class, 'last_notification_id');
    }

    public function latestProvider()
    {
        return $this->belongsTo(Provider::class, 'last_notified_provider_id');
    }

    public function markResponse($type)
    {
        $response_message = App::make(ResponseMessage::class);
        $response_message->call_id = $this->id;
        $response_message->notification_id = $this->last_notification_id;
        $response_message->provider_id = $this->last_notified_provider_id;

        if ($this->isNotCompleted()) {
            $notification = $this->latestNotification;
            $notification->response = $type;
            $notification->status = 'responded';
            $notification->save();

            // Should not be set to responded until confirmation.
            $this->status = 'responded';
            $this->save();
        }

        return $response_message;
    }

    /**
     * Get the CallNote model relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function notes()
    {
        return $this->hasMany(CallNote::class);
    }

    public function notificationGroups()
    {
        return $this->belongsToMany(NotificationGroup::class, 'call_notification_group');
    }

    /**
     * Get the Notification model relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function outboundCalls()
    {
        return $this->belongsToMany(static::class, 'call_outbound_call', 'call_id', 'outbound_call_id')->orderBy('calls.created_at', 'desc');
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

    /**
     * Get the patient's Provider model relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function patientProvider()
    {
        return $this->belongsTo(Provider::class);
    }

    public function provider()
    {
        return $this->belongsTo(Provider::class, 'last_notified_provider_id');
    }

    /**
     * Get the Recording model relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function recordings()
    {
        return $this->hasMany(Recording::class);
    }

    public function resolve($completedById = null): void
    {
        $completedById = $completedById ?: $this->last_notified_provider_id;

        Log::info("Provider {$completedById} has completed call {$this->id}");

        if ($this->isNotCompleted()) {
            $this->status = 'complete';
            $this->completed_at = \Carbon\Carbon::now();

            $user = app('auth')->user();

            if ($user) {
                $this->completedByUser()->associate($user);
            } else {
                $this->completed_by_provider_id = $completedById;
            }

            $this->save();
        }
    }

    /**
     * Get the CallResponse model relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function responses()
    {
        return $this->hasMany(CallResponse::class);
    }

    public function scopeComplete($query)
    {
        return $query->whereNotNull('completed_at');
    }

    public function scopeForProvider($query, $provider_id)
    {
        return $query->where('last_notified_provider_id', $provider_id);
    }

    /**
     * Scope the query to get only inbound calls.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInbound($query)
    {
        return $query->where('direction', 'inbound');
    }

    public function scopeInboundArchived($query)
    {
        return $query->where('status', '=', 'archived')
            ->where('direction', '=', 'inbound');
    }

    public function scopeInboundComplete($query)
    {
        return $query->whereIn('status', ['complete', 'archived'])
            ->where('direction', '=', 'inbound');
    }

    public function scopeInboundForwardedForProvider($query, $provider_id)
    {
        return $query
            ->whereHas('forwards', function ($q) use ($provider_id): void {
                $q->where('forward_to_provider_id', '=', $provider_id);
            })
            ->where('direction', '=', 'inbound');
    }

    public function scopeInboundNew($query)
    {
        return $query->where('status', '=', 'new')->where('direction', '=', 'inbound');
    }

    /**
     * Scope the queries to only inbound calls that have sent notifications.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  array  $statuses
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInboundNotificationsSent($query, $statuses = [])
    {
        $statuses = array_merge(static::NOTIFICATION_SENT_STATUSES, $statuses);

        return $query
            ->inbound()
            ->whereIn('status', $statuses);
    }

    public function scopeInboundUnanswered($query)
    {
        return $query->whereIn('status', ['missed', 'notifications-sent'])
            ->where('direction', '=', 'inbound');
    }

    public function scopeIncomplete($query)
    {
        return $query->whereNull('completed_at');
    }

    public function scopeNonUrgent($query)
    {
        return $query->where('is_urgent', '=', 0);
    }

    /**
     * Scope a query to filter records based on a starting date.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string|null  $starts
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeStarts($query, $starts = null)
    {
        return $query->when(
            $starts,
            fn ($query) => $query->where('created_at', '>=', Carbon::parse($starts))
        );
    }

    /**
     * Scope a query to filter records based on an end date.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string|null  $ends
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeEnds($query, $ends = null)
    {
        return $query->when(
            $ends,
            fn ($query) => $query->where('created_at', '<=', Carbon::parse($ends))
        );
    }

    /**
     * Scope the query to get only new calls.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeNotifiablePendingCalls($query)
    {
        // Retrieve the default date range from the application configurations
        $defaultRange = ApplicationConfigurations::getConfigurationValueByName('omd_date_range');
        if (!empty($defaultRange)) {
            $starts = Carbon::now()->subDays($defaultRange);
            $ends = Carbon::now();
        } else {
            $starts = null;
            $ends = null;
        }

        return $query
            ->inboundNotificationsSent(['new'])
            ->incomplete()
            ->withoutSampleCalls()
            ->withoutPagingCalls()
            ->starts($starts)
            ->ends($ends);
    }

    public function scopeUrgent($query)
    {
        return $query->where('is_urgent', '=', 1);
    }

    /**
     * Scope the query to filter out any paging calls.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithoutPagingCalls($query)
    {
        return $query->where('source', config('constants.CALL_SOURCE.CALL'));
    }

    /**
     * Scope the query to get only real calls.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithoutSampleCalls($query)
    {
        return $query->where(function ($query): void {
            $query->where('is_sample', false)
                ->orWhereNull('is_sample');
        });
    }

    public function sendResponse($type, $data): void
    {
        $this->markResponse($type);
    }

    public function setAsComplete(): void
    {
        if ($this->isNotCompleted()) {
            $this->status = 'complete';
            $this->completed_at = \Carbon\Carbon::now();
            $this->save();
        }
    }

    /**
     * @return \App\Factories\CallNotificationFactory
     */
    protected function getNotificationFactory()
    {
        return app(CallNotificationFactory::class);
    }

    protected function getProviderOptionsAttribute($value)
    {
        return json_decode($value, true);
    }

    /**
     * Relationship: Call has many CallRequestMappings.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function callRequestMappings()
    {
        return $this->hasMany(CallRequestMapping::class);
    }

    /**
     * Relationship: Call has one patient profile.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function patientProfile()
    {
        return $this->hasOne(PatientProfile::class, 'phone_number', 'callback_number');
    }

    public function getWmaAllowReplyAttribute()
    {

        // Return false early if partner is not WMA-enabled
        if (!$this->partner?->wma_enabled) {
            return false;
        }

        // Return false if DOB is not valid
        if (!$this->isValidDob($this->patient_dob)) {
            return false;
        }
        
        // Access patient profile
        $campaignsJson = $this->patientProfile?->campaigns;

        // Decode JSON
        $campaigns = json_decode($campaignsJson, true);

        // Check for valid first campaign and opt-in status
        return isset($campaigns[0]['opt_in_status']) &&
            filter_var($campaigns[0]['opt_in_status'], FILTER_VALIDATE_BOOLEAN);
    }

    private function isValidDob($dob): bool
    {
        if (empty($dob)) {
            return false;
        }

        return isValidDobFormat($dob, 'm/d/Y');
    }

    public function scopeByIdAndPartner($query, $callId, $partnerId)
    {
        return $query->where('id', $callId)->where('partner_id', $partnerId);
    }


}
