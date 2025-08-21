<?php

namespace ClarusSharedModels\Models;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use ClarusSharedModels\Models\PreviousTimestampFormat;
use Carbon\Carbon;

class Partner extends Model
{
    use HasFactory, PreviousTimestampFormat, SoftDeletes;

    /**
     * The model's attributes.
     *
     * @var array
     */
    protected $attributes = [
        'active'                 => true,
        'allow_premium_option'   => true,
        'allow_voicemail_option' => true,
        'timezone'               => 'America/New_York',
        'paging_template_type'   => 'default',
        'completion_note_required' => false,
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'digest_emails'    => 'array',
        'stream_tenant_id' => 'string',
        'wma_enabled'      => 'boolean',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'label',
        'wma_display_name',
        'office_phone_number',
        'office_fax_number',
        'translator_number',
        'address_1',
        'address_2',
        'city',
        'state_province',
        'postal_code',
        'country',
        'active',
        'wma_enabled',
        'allow_voicemail_option',
        'allow_premium_option',
        'twilio_phone_number',
        'fallback_email',
        'fallback_phone_number',
        'old_digest_email',
        'digest_emails',
        'digest_emails_array',
        'digest_emails_comma',
        'timezone',
        'disable_patient_name',
        'disable_patient_dob',
        'allow_inbound_provider_calls',
        'inbound_provider_option_position',
        'auto_detect_calling_number',
        'ob_caller_id_number',
        'enable_mobile_beta',
        'disable_transcriptions',
        'enable_patient_call_recording',
        'enable_secure_chat',
        'enable_paging',
        'available_for_patients',
        'admin_note',
        'paging_template',
        'stream_tenant_id',
        'passcode',
        'rsm_fallback_email',
        'global_rsm_phone_number',
        'health_check_threshold',
        'paging_template_type',
        'partner_short_id',
        'completion_note_required',
        'complete_note_template',
        'sync_status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'active', 'allow_voicemail_option', 'allow_premium_option',
    ];

    /**
     * Retrieves a partner and their associated calendars by partner ID.
     *
     * This method looks for a partner by the given ID. If the partner exists, it returns a JSON response
     * containing the partner's associated calendars. If no partner is found, it returns a 404 error response
     * with a relevant message.
     *
     * @param int $partnerId The ID of the partner to fetch.
     * 
     * @return \Illuminate\Http\JsonResponse A JSON response containing either the partner's calendars or an error message.
     */
    public static function getPartnerCalendars($partnerId)
    {
        $partner = self::find($partnerId);
        if (!$partner) {
            return response()->json('Partner not found');
        }
        return response()->json($partner->calendars);

    }

    /**
     * Get the Calendar model relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function calendars()
    {
        return $this->hasMany('ClarusSharedModels\\Models\\Calendar');
    }

    /**
     * Get the CallDataElement model relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function callDataElements()
    {
        return $this->hasMany('ClarusSharedModels\\Models\\CallDataElement');
    }

    /**
     * Get the Call model relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function calls()
    {
        return $this->hasMany('ClarusSharedModels\\Models\\Call');
    }

    /**
     * Get the CallType model relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function callTypes()
    {
        return $this->hasMany('ClarusSharedModels\\Models\\CallType');
    }

    public function getDigestEmailsArrayAttribute()
    {
        if (isset($this->attributes['digest_emails'])) {
            return json_decode($this->attributes['digest_emails']);
        }
    }

    public function getDigestEmailsCommaAttribute()
    {
        $array = $this->getAttribute('digest_emails_array');

        if (is_array($array)) {
            return implode(',', $array);
        }
    }

    /**
     * Get the current on call provider.
     *
     * @return \App\Models\Provider
     */
    public function getOnCallProvider()
    {
        $calendar = $this->calendars()->where('is_default', true)->first();

        if (! $calendar) {
            return false;
        }

        $resolverClass = 'App\\Calendars\\OncallResolver';
        if (class_exists($resolverClass)) {
            return (new $resolverClass($calendar))->resolve();
        }
        
        return false;
    }

    /**
     * Get the current on call provider by calendar id.
     *
     * @param  int  $calendar
     * @param  \App\Models\Call  $call
     * @return \App\Models\Provider|bool
     */
    public function getOnCallProviderByCalendar($calendar, $call)
    {
        $calendar = $this->calendars()->find($calendar);

        if (! $calendar) {
            Log::info('Could not retrieve on call provider. Calendar was not found.', [$calendar, $call->id]);
            return false;
        }

        $resolverClass = 'App\\Calendars\\OncallResolver';
        if (class_exists($resolverClass)) {
            return (new $resolverClass($calendar))->forCall($call)->resolve();
        }
        
        return false;
    }

    /**
     * Get the current on call provider by calender id and timestamp.
     *
     * @param  int  $calendar
     * @param  \App\Models\Call  $call
     * @param  \Carbon\Carbon  $time
     * @return \App\Models\Provider|bool
     */
    public function getOnCallProviderByCalendarAndTime($calendar, $call, $time)
    {
        $calendar = $this->calendars()->find($calendar);

        if (! $calendar) {
            return false;
        }

        return (new OncallResolver($calendar))
            ->forCall($call)
            ->duringTime($time)
            ->resolve();
    }

    /**
     * Get the current on call secondary provider.
     *
     * @return \App\Models\Provider
     */
    public function getOnCallSecondaryProvider()
    {
        $calendar = $this->calendars()->where('is_default', true)->first();

        if (! $calendar) {
            return false;
        }

        return (new OncallResolver($calendar))->useSecondaryFirst()->resolve();
    }

    /**
     * Get the current on call secondary provider by calendar id.
     *
     * @param  int  $calendar
     * @param  \App\Models\Call  $call
     * @return \App\Models\Provider
     */
    public function getOnCallSecondaryProviderByCalendar($calendar, $call)
    {
        $calendar = $this->calendars()->find($calendar);

        if (! $calendar) {
            return false;
        }

        return (new OncallResolver($calendar))->forCall($call)->useSecondaryFirst()->resolve();
    }

    /**
     * Get the on call secondary provider by calendar id and timestamp.
     *
     * @param  int  $calendar
     * @param  \App\Models\Call  $call
     * @param  \Carbon\Carbon  $time
     * @return \App\Models\Provider
     */
    public function getOnCallSecondaryProviderByCalendarAndTime($calendar, $call, $time)
    {
        $calendar = $this->calendars()->find($calendar);

        if (! $calendar) {
            return false;
        }

        return (new OncallResolver($calendar))
            ->forCall($call)
            ->duringTime($time)
            ->useSecondaryFirst()
            ->resolve();
    }

    /**
     * Checks if EHR (Electronic Health Records) is enabled for the partner.
     *
     * This method determines whether the current partner has EHR settings configured.
     * If the partner has associated EHR settings, it returns true, indicating that EHR is enabled.
     * Otherwise, it returns false.
     *
     * @return bool True if EHR is enabled, false otherwise.
     */
    public function ehrEnabled(): bool
    {
        return $this->partnerEhrSetting !== null;
    }

    /**
     * Get the EHR service instance for the partner.
     *
     * This method checks if the partner has EHR enabled and returns the appropriate EHR service instance.
     * If the partner is not EHR enabled, an exception is thrown.
     *
     * @return \App\Services\EHR\EHRServiceInterface
     * @throws \Exception If the partner is not EHR enabled.
     */
    public function getEhrService()
    {
        if ($this->partnerEhrSetting) {
            $factoryClass = 'App\\Services\\EHR\\EHRServiceFactory';
            if (class_exists($factoryClass)) {
                $ehrDriver = $this->partnerEhrSetting->ehrDriver->internal_name;
                return $factoryClass::make($ehrDriver, $this);
            }
        }

        throw new \Exception('Partner is not EHR enabled');
    }

    /**
     * Determine if the partner has a registered (A2P 10DLC) Twilio phone number.
     *
     * @return bool
     */
    public function hasRegisteredTwilioPhoneNumber(): bool
    {
        return ! is_null($this->twilio_a2p_registered_at);
    }

    /**
     * Get the IvrRoutePlan model relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function ivrRoutePlans()
    {
        return $this->hasMany('ClarusSharedModels\\Models\\IvrRoutePlan');
    }

    /**
     * Get the linked Partner model relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function linkedPartners()
    {
        return $this->belongsToMany(self::class, 'linked_partner_partner', 'partner_id', 'linked_partner_id');
    }

    public function linkedPartnersReports()
    {
        return $this->belongsToMany(self::class, 'linked_partners_for_reports', 'partner_id', 'linked_partner_id');
    }

    /**
     * Get the linked to Partner model relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function linkedToPartners()
    {
        return $this->belongsToMany(self::class, 'linked_partner_partner', 'linked_partner_id', 'partner_id');
    }

    /**
     * Retrieves all partner office managers.
     *
     * @return \App\Models\User
     */
    public function officeManagers()
    {
        return $this->belongsToMany('ClarusSharedModels\\Models\\User', 'role_user')
            ->using('ClarusSharedModels\\Models\\RoleUser')
            ->withPivot('id', 'role_id')
            ->withTimestamps()
            ->whereHas('roles', function ($query): void {
                $roleClass = 'ClarusSharedModels\\Models\\Role';
                $officeManagerRole = class_exists($roleClass) && defined($roleClass.'::OFFICE_MANAGER') ? $roleClass::OFFICE_MANAGER : 'office_manager';
                $query->where('roles.name', $officeManagerRole);
            });
    }

    /**
     * Get the PartnerFacility model relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function partnerFacilities()
    {
        return $this->hasMany('ClarusSharedModels\\Models\\PartnerFacility');
    }

    /**
     * Get the PartnerGroup model relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function partnerGroup()
    {
        return $this->belongsTo('ClarusSharedModels\\Models\\PartnerGroup');
    }

    /**
     * Get the PartnerProvider model relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function partnerProviders()
    {
        return $this->hasMany('ClarusSharedModels\\Models\\PartnerProvider');
    }

    /**
     * Get the Patient model relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function patients()
    {
        return $this->hasMany('ClarusSharedModels\\Models\\Patient');
    }

    /**
     * Get the ProviderGroup model relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function providerGroups()
    {
        return $this->hasMany('ClarusSharedModels\\Models\\ProviderGroup');
    }

    /**
     * Get the Provider model relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function providers()
    {
        return $this->hasMany('ClarusSharedModels\\Models\\Provider');
    }

    /**
     * Get the Role model relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function roles()
    {
        return $this->belongsToMany('ClarusSharedModels\\Models\\Role', 'role_user')
            ->using('ClarusSharedModels\\Models\\RoleUser')
            ->withPivot('id', 'user_id')
            ->withTimestamps();
    }

    /**
     * Get the PartnerSchedule model relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function schedules()
    {
        return $this->hasMany('ClarusSharedModels\\Models\\PartnerSchedule');
    }

    /**
     * Scope a query to only include active partners.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('active', '=', true);
    }

    /**
     * Set the partner's digest emails array.
     *
     * @param  array  $value
     * @return void
     */
    public function setDigestEmailsArrayAttribute($value): void
    {
        $this->attributes['digest_emails'] = json_encode($value);
    }

    /**
     * Set the partner's digest emails comma attribute.
     *
     * @param  string  $value
     * @return void
     */
    public function setDigestEmailsCommaAttribute($value): void
    {
        $array = explode(',', $value);

        $this->setAttribute('digest_emails_array', $array);
    }

    /**
     * Set the fallback phone number attribute.
     *
     * @param  string  $value
     * @return void
     */
    public function setFallbackPhoneNumberAttribute($value): void
    {
        $this->attributes['fallback_phone_number'] = scrub_twilio_phone_input($value);
    }

    /**
     * Set this partner's ob caller id number.
     *
     * @param  string  $value
     * @return void
     */
    public function setObCallerIdNumberAttribute($value): void
    {
        $this->attributes['ob_caller_id_number'] = scrub_twilio_phone_input($value);
    }

    /**
     * Set the office fax number attribute.
     *
     * @param  string  $value
     * @return void
     */
    public function setOfficeFaxNumberAttribute($value)
    {
        if (! $value) {
            return $this->attributes['office_fax_number'] = null;
        }

        $this->attributes['office_fax_number'] = scrub_twilio_phone_input($value);
    }

    /**
     * Set the office phone number attribute.
     *
     * @param  string  $value
     * @return void
     */
    public function setOfficePhoneNumberAttribute($value)
    {
        if (! $value) {
            return $this->attributes['office_phone_number'] = null;
        }

        $this->attributes['office_phone_number'] = scrub_twilio_phone_input($value);
    }

    /**
     * Set the partner's twilio phone number.
     *
     * @param  string  $value
     * @return void
     */
    public function setTwilioPhoneNumberAttribute($value): void
    {
        $this->attributes['twilio_phone_number'] = scrub_twilio_phone_input($value);
    }

    /**
     * Get the TimeBlock model relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function timeBlocks()
    {
        return $this->hasMany('ClarusSharedModels\\Models\\TimeBlock');
    }

    /**
     * Get the TwilioScript model relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function twilioScripts()
    {
        return $this->hasMany('ClarusSharedModels\\Models\\TwilioScript');
    }

    /**
     * Get the User model relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function users()
    {
        return $this->belongsToMany('ClarusSharedModels\\Models\\User', 'partner_user')->withTimestamps();
    }

    /**
     * Get the Partner EHR settting model relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function partnerEhrSetting()
    {
        return $this->hasOne('ClarusSharedModels\\Models\\PartnerEhrSetting');
    }

    /**
     * Get the default validation rules for the partner model.
     *
     * @param  int|null  $id
     * @return array
     */
    public static function rules($id = null)
    {
        $ignoreID = $id ?: 'null';

        return [
            'name'                  => 'required',
            'twilio_phone_number'   => 'required|phone|unique:partners,twilio_phone_number,'.$ignoreID,
            'fallback_email'        => 'required',
            'fallback_phone_number' => 'required|phone',
            'ob_caller_id_number'   => 'phone',
            'applications'          => 'required',
        ];
    }

    public function getCallCountForPartnerByCallType($params)
    {
        $query = DB::table('call_types')
            ->selectRaw("COALESCE(COUNT(calls.id), 0) as count, call_types.id")
            ->join('partners', 'partners.id', '=', 'call_types.partner_id') // Ensure only call_types linked to the partner
            ->leftJoin('call_call_type', 'call_types.id', '=', 'call_call_type.call_type_id') // Link call_types to calls
            ->leftJoin('calls', function ($join) use ($params) {
                $join->on('call_call_type.call_id', '=', 'calls.id')
                    ->where('calls.partner_id', $params['partnerId'])
                    ->where('calls.direction', 'inbound')
                    ->whereNull('calls.completed_at')
                    ->whereNull('calls.dismissed_by')
                    ->where('calls.limited_visibility', false)
                    ->where('calls.source', config('constants.CALL_SOURCE.CALL'))
                    ->whereIn('calls.status', ['new', 'missed', 'notifications-sent', 'responded', 'in-progress', 'archived'])
                    ->where(function ($query) {
                        $query->where('calls.is_sample', false)
                            ->orWhereNull('calls.is_sample');
                    });

                // Include the date filters directly in the join
                if (!empty($params['starts']) && !empty($params['ends'])) {
                    $join->whereDate('calls.created_at', '>=', $params['starts'])
                        ->whereDate('calls.created_at', '<=', $params['ends']);
                }
            })
            ->where('partners.id', $params['partnerId']);
            
        return $query->groupBy('call_types.id')->get();
    }

    public function getCallCount($params)
    {
        $query = DB::table('calls')
            ->where('calls.partner_id', $params['partnerId'])
            ->where('calls.direction', 'inbound')
            ->whereNull('calls.completed_at')
            ->where('calls.limited_visibility', false)
            ->where('calls.source', config('constants.CALL_SOURCE.CALL'))
            ->whereIn('calls.status', ['new', 'missed', 'notifications-sent', 'complete', 'responded', 'in-progress', 'archived'])
            ->where(function($query) {
                $query->where('calls.is_sample', false)
                      ->orWhereNull('calls.is_sample');
            })
            ->where(function ($query) {
                $query->where('calls.dismissed_by', '=', null);
            });
        
        if (!empty($params['starts']) && !empty($params['ends'])) {
            $query->whereDate('calls.created_at', '>=', $params['starts']);
            $query->whereDate('calls.created_at', '<=', $params['ends']);
        }

        return $query;
    }

    /**
     * Retrieve call count metadata for the partner.
     *
     * @return array
     */
    public function fetchCallCountMetadata() {
        // Retrieve the default date range from the application configurations
        $configClass = 'ClarusSharedModels\\Models\\ApplicationConfigurations';
        $defaultRange = class_exists($configClass) ? $configClass::getConfigurationValueByName('omd_date_range') : null;
        if (!empty($defaultRange)) {
            $starts = Carbon::now()->subDays($defaultRange);
            $ends = Carbon::now();
        } else {
            $starts = null;
            $ends = null;
        }

        $params = [
            'partnerId' => $this->id,
            'starts'    => $starts,
            'ends'      => $ends
        ];

        $query = $this->getCallCount($params);
        $nonUrgentQuery = clone $query;
        $urgentQuery = clone $query;
        $callTypesCount = $this->getCallCountForPartnerByCallType($params);

        return [
            'urgent'     => $urgentQuery->where('is_urgent', true)->count(),
            'non_urgent' => $nonUrgentQuery->where('is_urgent', false)->count(),
            'total'      => $query->count(),
            'call_types' => $callTypesCount
        ];
    }

    /**
     * Get the reports associated with the partner.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function reports()
    {
        return $this->hasMany('ClarusSharedModels\\Models\\Report', 'generated_by_partner');
    }

    /**
     * Updates the sync_status attribute of the Partner model.
     *
     * This method adds or updates a specific key in the sync_status JSON
     * object with the provided boolean value. If sync_status is currently
     * null, it initializes it as an empty JSON object.
     *
     * @param string $statusKey The key to be added or updated in the sync_status.
     * @param bool $statusValue The boolean value to set for the specified key.
     *
     * @return void
     */

    public function updateSyncStatus(string $statusKey, bool $statusValue): void
    {
        // If sync_status is null, initialize it as an empty JSON object
        $currentStatus = $this->sync_status ?? json_encode(new \stdClass());

        // Decode the current status to an associative array
        $statusArray = json_decode($currentStatus, true);

        // Update the status with the new key and value
        $statusArray[$statusKey] = $statusValue;

        // Encode it back to JSON and save
        $this->sync_status = json_encode($statusArray);
        $this->save();
    }

     /**
     * Retrieve partner details based on the provided short ID.
     *
     * This method queries the database to get the partner's ID, short ID, display name, and full name
     * by matching the `partner_short_id` field with the provided `$shortId`.
     *
     * @param string $shortId The unique short ID of the partner.
     *
     * @return \Illuminate\Database\Eloquent\Model|null Returns the first matching record as an Eloquent model, or null if no match is found.
     */
    public static function getPartnerDetailsByShortId($shortId)
    {
        return self::select('id', 'name', 'office_phone_number', 'partner_short_id', 'wma_display_name', 'wma_enabled')
            ->where('partner_short_id', $shortId)
            ->first();
    }

}
