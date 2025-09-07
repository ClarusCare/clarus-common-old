<?php

namespace ClarusCommon\Models;

use Illuminate\Database\Eloquent\Model;

class IvrModule extends Model
{
    use PreviousTimestampFormat;

    public const CALLER_ID_OPTION_INBOUND = 'inbound';

    public const CALLER_ID_OPTION_PARTNER = 'partner';

    public static $rules = [];

    public static $types = [
        'end_call'                => 'End Call',
        'gather_birthdate'        => 'Gather Birthdate',
        'gather_callback_number'  => 'Gather Callback Number',
        'gather_pharmacy_number'  => 'Gather Pharmacy Number',
        'gather_primary_provider' => 'Gather Primary Provider',
        'gather_recording'        => 'Gather Recording',
        'external_provider'       => 'External Provider',
        'path_selection'          => 'Path Selection',
        'pending_completion'      => 'Pending Completion',
        'play_message'            => 'Play Message',
        'smart_transfer'          => 'Smart Transfer',
        'webform'                 => 'Webform',
        // 'wma_allow_reply'         => 'WMA Allow Reply',
    ];

    public static $updateRules = [];

    protected $appends = ['option_number', 'options'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'type',
        'notification_scheme',
        'recording_type',
        'ivr_route_plan_id',
        'twilio_script_id',
        'secondary_twilio_script_id',
        'provider_group_id',
        'schedule_calendar_id',
        'call_type_id',
        'options',
        'calendar_id',
    ];

    public function allChildIvrModules()
    {
        return $this->childIvrModules()->with('allChildIvrModules');
    }

    /**
     * Get the Calendar model relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function calendar()
    {
        return $this->belongsTo(Calendar::class);
    }

    public function callType()
    {
        return $this->belongsTo(CallType::class);
    }

    public function childIvrModules()
    {
        return $this->belongsToMany(self::class, 'child_ivr_module_ivr_module', 'ivr_module_id', 'child_ivr_module_id')->withPivot('option_number');
    }

    public function getOptionNumberAttribute()
    {
        if ($this->parentIvrModules->count()) {
            return $this->parentIvrModules->first()->pivot->option_number;
        }
    }

    public function getOptionsAttribute()
    {
        if (isset($this->attributes['options'])) {
            return json_decode($this->attributes['options']);
        }
    }

    public function ivrRoutePlan()
    {
        return $this->belongsTo(IvrRoutePlan::class, 'ivr_route_plan_id');
    }

    public function parentIvrModules()
    {
        return $this->belongsToMany(self::class, 'child_ivr_module_ivr_module', 'child_ivr_module_id', 'ivr_module_id')->withPivot('option_number');
    }

    public function providerGroup()
    {
        return $this->belongsTo(ProviderGroup::class);
    }

    public function secondaryTwilioScript()
    {
        return $this->belongsTo(TwilioScript::class, 'secondary_twilio_script_id');
    }

    public function twilioScript()
    {
        return $this->belongsTo(TwilioScript::class, 'twilio_script_id');
    }
}
