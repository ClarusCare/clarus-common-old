<?php

namespace ClarusCommon\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;

class Patient extends Eloquent
{
    use PreviousTimestampFormat;

    public static $rules = [
        'first_name' => 'required',
        'last_name'  => 'required',
    ];

    protected $fillable = [
        'unique_id',
        'first_name',
        'middle_name',
        'last_name',
        'date_of_birth',
        'partner_id',
    ];

    protected $guarded = [];

    protected $hidden = [];

    public function __construct($attributes = [], $exists = false)
    {
        parent::__construct($attributes, $exists);
    }

    public function getFullNameAttribute()
    {
        return $this->attributes['first_name'].' '.$this->attributes['last_name'];
    }

    public function partner()
    {
        return $this->belongsTo(\App\Models\Partner::class);
    }

    public function phoneNumbers()
    {
        return $this->morphMany(\App\Models\PhoneNumber::class, 'phoneable');
    }
}
