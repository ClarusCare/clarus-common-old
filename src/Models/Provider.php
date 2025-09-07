<?php

namespace ClarusCommon\Models;

use Exception;
use ClarusCommon\Traits\Eventable;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Provider extends Eloquent
{
    use Eventable, HasFactory, PreviousTimestampFormat, SoftDeletes;

    public const TYPE_EXTERNAL = 'external';

    public const TYPE_INTERNAL = 'internal';

    public static $rules = [
        'user_id'                => 'required|min:1|not_in:0',
        'email'                  => 'required|email',
        'phone_number'           => 'required|phone',
        'type'                   => 'required',
        'pager_email_address'    => 'nullable|email',
        'pager_phone_number'     => 'nullable|phone',
        'secondary_phone_number' => 'phone|required_if:allow_secondary_voice_notifications,1|required_if:allow_secondary_sms_notifications,1',
    ];

    protected $appends = [
    ];

    protected $cachedNotificationProfile;

    protected $fillable = [
        'title',
        'active',
        'user_id',
        'partner_id',
        'patient_available',
        'type',
        'full_name',
        'color',
        'quick_connect',
    ];

    protected $guarded = [];

    public function __construct($attributes = [], $exists = false)
    {
        //Set default values
        $this->active = true;
        $this->type = 'internal';

        parent::__construct($attributes, $exists);
    }

    public function activePartnerProviders()
    {
        return $this->partnerProviders()
            ->where('active', true)
            ->where('partner_id', '<>', $this->attributes['partner_id']);
    }

    /**
     * Get the event group coverage's Event model relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function coveringProviderGroupEvents()
    {
        return $this->belongsToMany(
            Event::class,
            'event_group_coverages',
        )
            ->withPivot('id', 'position', 'group_member_id')
            ->withTimestamps();
    }

    public function getAllowEmailNotificationsAttribute()
    {
        return $this->getNotificationProfile() ? $this->getNotificationProfile()->allow_email_notifications : null;
    }

    public function getAllowMobileNotificationsAttribute()
    {
        return $this->getNotificationProfile() ? $this->getNotificationProfile()->allow_mobile_notifications : null;
    }

    public function getAllowPagerNotificationsAttribute()
    {
        return $this->getNotificationProfile() ? $this->getNotificationProfile()->allow_pager_notifications : null;
    }

    public function getAllowSecondarySmsNotificationsAttribute()
    {
        return $this->getNotificationProfile() ? $this->getNotificationProfile()->allow_secondary_sms_notifications : null;
    }

    public function getAllowSecondaryVoiceNotificationsAttribute()
    {
        return $this->getNotificationProfile() ? $this->getNotificationProfile()->allow_secondary_voice_notifications : null;
    }

    public function getAllowSmsNotificationsAttribute()
    {
        return $this->getNotificationProfile() ? $this->getNotificationProfile()->allow_sms_notifications : null;
    }

    public function getAllowVoiceNotificationsAttribute()
    {
        return $this->getNotificationProfile() ? $this->getNotificationProfile()->allow_voice_notifications : null;
    }

    /**
     * Get the provider's display name (last-name, first-initial).
     *
     * @return string|null
     */
    public function getDisplayNameAttribute()
    {
        try {
            if (! $this->shouldUseProviderFullName()) {
                return $this->getDisplayNameFromUser();
            }

            return $this->getDisplayNameFromFullName();
        } catch (Exception $e) {
            return;
        }
    }

    public function getEmailAddressAttribute()
    {
        return $this->getNotificationProfile() ? $this->getNotificationProfile()->email : null;
    }

    public function getEmailAttribute()
    {
        return $this->getNotificationProfile() ? $this->getNotificationProfile()->email : null;
    }

    public function getEnablePrimaryPhoneNumberAttribute()
    {
        return $this->getNotificationProfile() ? $this->getNotificationProfile()->enable_primary_phone_number : null;
    }

    public function getEnableSecondaryPhoneNumberAttribute()
    {
        return $this->getNotificationProfile() ? $this->getNotificationProfile()->enable_secondary_phone_number : null;
    }

    /**
     * Get the provider's name attribute.
     *
     * @return string
     */
    public function getNameAttribute()
    {
        if (! $this->user) {
            return $this->full_name;
        }

        return $this->user->full_name;
    }

    public function getNotificationEmail()
    {
        $notificationPref = ($this->getNotificationProfile() ? $this->getNotificationProfile()->email : null);

        if (! $notificationPref || empty($notificationPref)) {
            return $this->user ? $this->user->email : '';
        }

        return $notificationPref;
    }

    public function getNotificationPhoneNumber()
    {
        $notificationPref = ($this->getNotificationProfile() ? $this->getNotificationProfile()->phone_number : null);

        if (! $notificationPref || empty($notificationPref)) {
            return $this->user ? $this->user->phone_number : '';
        }

        return $notificationPref;
    }

    /**
     * @return NotificationProfile
     */
    public function getNotificationProfile()
    {
        $user = $this->user;

        if ($user) {
            return $user->notificationProfile;
        }

        if ($this->notificationProfile instanceof NotificationProfile) {
            return $this->notificationProfile;
        }
    }

    public function getOutstandingCalls()
    {
        return $this->notifiedCalls()
            ->where('status', 'notifications-sent')
            ->where('is_urgent', 1)
            ->count();
    }

    public function getPagerEmailAddressAttribute()
    {
        return $this->getNotificationProfile() ? $this->getNotificationProfile()->pager_email_address : null;
    }

    public function getPagerPhoneNumberAttribute()
    {
        return $this->getNotificationProfile() ? $this->getNotificationProfile()->pager_phone_number : null;
    }

    /**
     * Verify if the model is patient available.
     *
     * @return bool
     */
    public function getPatientAvailableAttribute()
    {
        return ! is_null($this->available_for_patients_at);
    }

    public function getPhoneNumberAttribute()
    {
        return $this->getNotificationProfile() ? $this->getNotificationProfile()->phone_number : null;
    }

    public function getSecondaryPhoneNumberAttribute()
    {
        return $this->getNotificationProfile() ? $this->getNotificationProfile()->secondary_phone_number : null;
    }

    /**
     * Get the GroupMember model relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function groups()
    {
        return $this->hasMany(GroupMember::class);
    }

    /**
     * Determine if the provider is an external provider.
     *
     * @return bool
     */
    public function isExternal(): bool
    {
        return $this->type === static::TYPE_EXTERNAL;
    }

    /**
     * Determine if the provider is an internal provider.
     *
     * @return bool
     */
    public function isInternal(): bool
    {
        return $this->type === static::TYPE_INTERNAL;
    }

    public function notificationProfile()
    {
        return $this->morphOne(NotificationProfile::class, 'notifiable');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function notifiedCalls()
    {
        return $this->hasMany(Call::class, 'last_notified_provider_id');
    }

    public function partner()
    {
        return $this->belongsTo(Partner::class);
    }

    public function partnerProviders()
    {
        return $this->hasMany(PartnerProvider::class);
    }

    /**
     * Get the Partner model relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function partners()
    {
        return $this->belongsToMany(Partner::class, 'partner_providers')
            ->withPivot('active')
            ->withTimestamps();
    }

    public function pointsOfContact()
    {
        return $this->hasMany(PointOfContact::class);
    }

    public function providerGroups()
    {
        return $this->belongsToMany(ProviderGroup::class, 'group_members')
            ->withPivot('id')
            ->withTimestamps()
            ->withPivot('position')
            ->orderByPivot('position');
    }

    public function push_notification_logs()
    {
        return $this->hasMany(PushNotificationLog::class);
    }

    public function pushTokens()
    {
        return $this->hasMany(PushToken::class);
    }

    /**
     * Get the EventRequest model relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function receivedEventRequests()
    {
        return $this->hasMany(EventRequest::class, 'recipient_id');
    }

    public function rules()
    {
        return [
            'user_id'                => 'required|min:1|not_in:0',
            'email'                  => 'required|email',
            'phone_number'           => 'required|phone',
            'pager_email_address'    => 'email',
            'pager_phone_number'     => 'phone',
            'secondary_phone_number' => 'phone|required_if:enable_secondary_phone_number,1',
        ];
    }

    /**
     * Scope the query to only internal providers.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInternalOnly(Builder $query)
    {
        return $query->where('type', static::TYPE_INTERNAL);
    }

    /**
     * Get the EventRequest model relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function sentEventRequests()
    {
        return $this->hasMany(EventRequest::class, 'sender_id');
    }

    /**
     * Mark the model as patient available depending on the value.
     *
     * @param  bool  $value
     * @return void
     */
    public function setPatientAvailableAttribute($value): void
    {
        $timestamp = null;

        if ($value) {
            $timestamp = $this->available_for_patients_at ?: now();
        }

        $this->available_for_patients_at = $timestamp;
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

    public static function externalRules()
    {
        return [
            'email'                 => 'required|email',
            'phone_number'          => 'required|phone',
            'type'                  => 'required',
            'full_name'             => 'required',
            'pager_email_address'   => 'nullable|email',
            'pager_phone_number'    => 'nullable|phone',
        ];
    }

    public static function internalRules()
    {
        return [
            'user_id'                => 'required|min:1|not_in:0',
            'email'                  => 'required|email',
            'phone_number'           => 'required|phone',
            'type'                   => 'required',
            'pager_email_address'    => 'nullable|email',
            'pager_phone_number'     => 'nullable|phone',
            'secondary_phone_number' => 'phone|required_if:allow_secondary_voice_notifications,1|required_if:allow_secondary_sms_notifications,1',
        ];
    }

    /**
     * Append a period to the given display name.
     *
     * @param  string  $displayName
     * @return string
     */
    protected function appendPeriodToDisplayName(string $displayName)
    {
        $name = Str::of($displayName);

        if ($name->endsWith('.')) {
            return $name;
        }

        return (string) $name->append('.');
    }

    /**
     * Get the provider's display name from their full name attribute.
     *
     * @return string|null
     */
    protected function getDisplayNameFromFullName()
    {
        if (! $this->full_name) {
            return;
        }

        $parts = Str::of($this->removeTitlesFromName($this->full_name))->split('/\s/');

        if ($parts->count() < 2) {
            return $this->full_name;
        }

        $initial = (string) Str::of($parts[0])->substr(0, 1);

        unset($parts[0]);

        $trimmed = $parts->map(fn ($part) => trim($part))->toArray();

        $names = implode(' ', $trimmed);

        return $this->appendPeriodToDisplayName("{$names}, {$initial}");
    }

    /**
     * Get the display name from the user's names.
     *
     * @return string|null
     */
    protected function getDisplayNameFromUser()
    {
        $firstName = $this->removeTitlesFromName($this->user->first_name);

        $initial = Str::of($firstName)->substr(0, 1);

        $displayName = Str::of("{$this->user->last_name}, {$initial}");

        return $this->appendPeriodToDisplayName($displayName);
    }

    /**
     * Remove a formal title from the given name.
     *
     * @param  string  $name
     * @return string
     */
    protected function removeTitlesFromName(string $name)
    {
        return preg_replace('/^(Mr\.*\sJustice|Jr\.*\s|Mr\.*\s+|Mrs\.*\s+|Ms\.\s+|Dr\.*\s+|Justice|etc.)*(.*)$/is', '$2', trim($name));
    }

    /**
     * Determine if the display name should use the provider's full name.
     *
     * @return bool
     */
    protected function shouldUseProviderFullName(): bool
    {
        if (! $this->relationLoaded('user')) {
            return true;
        }

        if ($this->type === static::TYPE_EXTERNAL) {
            return true;
        }

        return is_null($this->user);
    }
}
