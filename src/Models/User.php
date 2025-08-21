<?php

namespace ClarusSharedModels\Models;

use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use ClarusSharedModels\Traits\HasRoles;
use ClarusSharedModels\Traits\AttachesS3Files;
use ClarusSharedModels\Models\PreviousTimestampFormat;


// Conditional interface for auditing
if (interface_exists('OwenIt\\Auditing\\Contracts\\Auditable')) {
    interface UserAuditableInterface extends \OwenIt\Auditing\Contracts\Auditable {}
} else {
    interface UserAuditableInterface {}
}

class User extends Authenticatable implements UserAuditableInterface
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;
    use HasRoles, AttachesS3Files, PreviousTimestampFormat;
    
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        
        // Add auditing trait if available
        if (trait_exists('OwenIt\\Auditing\\Auditable')) {
            $this->initializeAuditable();
        }
    }
    
    protected function initializeAuditable()
    {
        // Initialize auditing if trait exists
        if (method_exists($this, 'bootAuditable')) {
            $this->bootAuditable();
        }
    }

    protected $table = 'users';

    protected $fillable = [
        'name',
        'first_name',
        'last_name',
        'email',
        'password',
        'partner_id',
        'phonetic_name',
        'readback_option',
        'receives_on_call_report',
        'accessible_calendars',
        'view_own_pages_only',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'chat_channel_name',
    ];

    protected $casts = [
        'accessible_calendars' => 'json',
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    protected $appends = [
        'full_name',
        'profile_image',
        'profile_image_retina',
        'intercom_hash_web',
    ];

    protected $attachments = [
        'audio' => [
            'storage_path' => 'audios/:id/original',
            'keep_files'   => false,
        ],
    ];

    // Accessors & Mutators

    public function getFullNameAttribute()
    {
        if (!empty($this->name)) {
            return $this->name;
        }
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function getProfileImageAttribute()
    {
        return 'https://www.gravatar.com/avatar/' . md5(strtolower(trim($this->email))) . '?d=mp&r=g';
    }

    public function getProfileImageRetinaAttribute()
    {
        return 'https://www.gravatar.com/avatar/' . md5(strtolower(trim($this->email))) . '?s=512&d=mp&r=g';
    }

    public function getIntercomHashWebAttribute()
    {
        return hash_hmac('sha256', $this->id, env('INTERCOM_SECRET_WEB'));
    }

    public function setEmailAttribute($value): void
    {
        $this->attributes['email'] = strtolower($value);
    }

    public function setPasswordAttribute($value): void
    {
        if ($value && Hash::needsRehash($value)) {
            $value = Hash::make($value);
        }

        if ($value) {
            $this->attributes['password'] = $value;
        }
    }

    // Relationships

    public function providers()
    {
        return $this->hasMany('App\\Models\\Provider');
    }

    public function notificationProfile()
    {
        return $this->morphOne('App\\Models\\NotificationProfile', 'notifiable');
    }

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

    public function pushTokens()
    {
        return $this->hasMany('App\\Models\\PushToken');
    }

    public function legacyRoles()
    {
        return $this->belongsToMany('App\\Models\\Role', 'roles_users');
    }

    public function notes()
    {
        return $this->hasMany('App\\Models\\CallNote');
    }

    // Methods

    public function canAccessPartners($partners, $strict = true)
    {
        if (is_int($partners)) {
            $partners = [$partners];
        }

        $partnerClass = 'App\\Models\\Partner';
        if (class_exists($partnerClass) && $partners instanceof $partnerClass) {
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

    public function getCallerId(): void
    {
        $this->id;
    }

    public function getCallerType()
    {
        return 'users';
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

    public function isPersonalAccessToken($token)
    {
        return $token->client->personal_access_client && !$token->revoked;
    }

    public function isProvider(): bool
    {
        if ($this->relationLoaded('providers')) {
            return $this->providers->isNotEmpty();
        }

        return $this->providers()->exists();
    }

    public function routeNotificationForApn()
    {
        return $this->pushTokens()->where('service', 'apns')->active()->get()->pluck('token')->all();
    }

    public function routeNotificationForFcm()
    {
        $maxLimit = config('firebase.max_limit_for_notification');
        return $this->pushTokens()->active()->orderBy('updated_at', 'desc')->take($maxLimit)->get()->pluck('token')->all();
    }

    public function routeNotificationForTwilio()
    {
        return scrub_twilio_phone_input($this->notificationProfile->phone_number);
    }

    public function sendPasswordResetNotification($token): void
    {
        // Try to use project's ResetPassword notification
        $notificationClass = 'App\\Notifications\\ResetPassword';
        if (class_exists($notificationClass)) {
            $this->notify(new $notificationClass($token));
        } else {
            // Fallback to Laravel's default
            parent::sendPasswordResetNotification($token);
        }
    }

    private function partnerIdAttributeIsEmpty()
    {
        return !isset($this->attributes['partner_id']) || !$this->attributes['partner_id'];
    }
}
