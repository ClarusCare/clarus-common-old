<?php

namespace ClarusSharedModels\Models;

use Illuminate\Support\Str;
use ClarusSharedModels\Traits\AttachesS3Files;
use Illuminate\Support\Collection;
use Laravel\Passport\HasApiTokens;
use Illuminate\Support\Facades\Hash;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use ClarusSharedModels\Traits\HasRoles;

class User extends Authenticatable
{
    use HasRoles;
    use HasFactory;
    use Notifiable;
    use SoftDeletes;
    use HasApiTokens;
    use AttachesS3Files;
    use PreviousTimestampFormat;

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'full_name', 'profile_image', 'profile_image_retina', 'intercom_hash_web',
    ];

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

    protected $casts = [
        'accessible_calendars' => 'json',
    ];

    /**
     * Attributes that can be mass assigned on this model.
     *
     * @var array
     */
    protected $fillable = [
        'first_name', 'last_name', 'email', 'password', 'partner_id',
        'phonetic_name', 'readback_option', 'receives_on_call_report',
        'accessible_calendars', 'view_own_pages_only',
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $guarded = ['password'];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token', 'chat_channel_name',
    ];

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'users';

    /**
     * Determine if the user is attached to the given partner(s).
     *
     * @param  array|\App\Models\Partner|int  $partners
     * @param  bool  $strict  Check if the user has access to all partners or at least one partner when false.
     * @return bool
     */
    public function canAccessPartners($partners, $strict = true)
    {
        if (is_int($partners)) {
            $partners = [$partners];
        }

        if ($partners instanceof Partner) {
            $partners = [$partners->id];
        }

        if ($partners instanceof Collection) {
            $partners = $partners->pluck('id')->toArray();
        }

        $count = $this->partners()->whereIn('partner_user.partner_id', $partners)->count();

        return $strict
            ? $count === count($partners)
            : $count > 0;
    }

    public function createNotificationProfile(): void
    {
        $this->notificationProfile()->create([
            'email' => $this->email,
        ]);
    }

    public function getCallerId()
    {
        return $this->id;
    }

    public function getCallerType()
    {
        return 'users';
    }

    public function getFullNameAttribute()
    {
        return (string) Str::of("{$this->first_name} {$this->last_name}")->trim();
    }

    public function getPartnerIdAttribute()
    {
        if ($this->partnerIdAttributeIsEmpty() && $this->provider) {
            return $this->provider->partner_id;
        }

        if (isset($this->attributes['partner_id'])) {
            return $this->attributes['partner_id'];
        }
    }

    public function getPersonalAccessTokensAttribute()
    {
        $self = $this;

        return $this->tokens->load('client')->filter(function ($token) use ($self) {
            return $self->isPersonalAccessToken($token);
        })->values();
    }

    public function getProfileImageAttribute()
    {
        return 'https://www.gravatar.com/avatar/'.md5(strtolower(trim($this->email))).'?d=mp&r=g';
    }

    public function getProfileImageRetinaAttribute()
    {
        return 'https://www.gravatar.com/avatar/'.md5(strtolower(trim($this->email))).'?s=512&d=mp&r=g';
    }

    public function isPersonalAccessToken($token)
    {
        return $token->client->personal_access_client && ! $token->revoked;
    }

    /**
     * Determine if the user has any providers.
     *
     * @return bool
     */
    public function isProvider(): bool
    {
        if ($this->relationLoaded('providers')) {
            return $this->providers->isNotEmpty();
        }

        return $this->providers()->exists();
    }

    /**
     * Get the legacy Role model relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function legacyRoles()
    {
        return $this->belongsToMany('App\\Models\\Role', 'roles_users');
    }

    /**
     * Get the CallNote model relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function notes()
    {
        return $this->hasMany('App\\Models\\CallNote');
    }

    public function notificationProfile()
    {
        return $this->morphOne('App\\Models\\NotificationProfile', 'notifiable');
    }

    /**
     * Get the Partner model relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function partners()
    {
        return $this->belongsToMany('App\\Models\\Partner', 'partner_user')->withTimestamps();
    }

    public function pointOfContactFor()
    {
        return $this->hasOne('App\\Models\\PointOfContact', 'user_id');
    }

    public function provider()
    {
        return $this->hasOne('App\\Models\\Provider');
    }

    public function providers()
    {
        return $this->hasMany('App\\Models\\Provider');
    }

    public function pushTokens()
    {
        return $this->hasMany('App\\Models\\PushToken');
    }

    /**
     * Specifies the user's APNS token(s).
     *
     * @return string
     */
    public function routeNotificationForApn()
    {
        return $this->pushTokens()->where('service', 'apns')->active()->get()->pluck('token')->all();
    }

    /**
     * Specifies the user's FCM token(s).
     *
     * @return string[]
     */
    public function routeNotificationForFcm()
    {
        $maxLimit = config('firebase.max_limit_for_notification');
        
        // Filters tokens updated (latest 10)
        return $this->pushTokens()->active()->orderBy('updated_at', 'desc')->take($maxLimit)->get()->pluck('token')->all();
    }

    /**
     * Route notifications for the Twilio channel to the user.
     *
     * @return string
     */
    public function routeNotificationForTwilio()
    {
        return scrub_twilio_phone_input($this->notificationProfile->phone_number);
    }

    /**
     * Send the password reset notification.
     *
     * @param  string  $token
     * @return void
     */
    public function sendPasswordResetNotification($token): void
    {
        // Use string reference to allow projects to define their own notification
        $notificationClass = 'App\\Notifications\\ResetPassword';
        if (class_exists($notificationClass)) {
            $this->notify(new $notificationClass($token));
        }
    }

    public function setEmailAttribute($value): void
    {
        $this->attributes['email'] = strtolower($value);
    }

    /**
     * Set and hash the password attribute.
     *
     * @param  string  $value
     * @return void
     */
    public function setPasswordAttribute($value): void
    {
        if ($value && Hash::needsRehash($value)) {
            $value = Hash::make($value);
        }

        if ($value) {
            $this->attributes['password'] = $value;
        }
    }

    private function partnerIdAttributeIsEmpty()
    {
        return ! isset($this->attributes['partner_id']) || ! $this->attributes['partner_id'];
    }

    public function getIntercomHashWebAttribute()
    {
        return hash_hmac(
            'sha256',
            $this->id,
            env('INTERCOM_SECRET_WEB')
        );
    }
}