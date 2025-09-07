<?php

namespace ClarusCommon\Models;

use Illuminate\Database\Eloquent\Model;

class IvrRoutePlan extends Model
{
    use PreviousTimestampFormat;

    public static $rules = [];

    public static $updateRules = [];

    protected $fillable = [];

    protected $hidden = [];

    public function childIvrModules()
    {
        return $this->belongsToMany(IvrModule::class, 'default_ivr_modules', 'ivr_route_plan_id', 'ivr_module_id');
    }

    public function defaultIvrModules()
    {
        return $this->belongsToMany(IvrModule::class, 'default_ivr_modules', 'ivr_route_plan_id', 'ivr_module_id');
    }

    public function ivrModules()
    {
        return $this->hasMany(IvrModule::class);
    }

    public function partner()
    {
        return $this->belongsTo(Partner::class, 'partner_id');
    }
}
