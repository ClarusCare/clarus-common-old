<?php

namespace ClarusSharedModels\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;

class PhoneNumber extends Eloquent
{
    use PreviousTimestampFormat;

    public static $rules = [
        'phone_number' => 'required|phone|digits:10',
    ];

    protected $fillable = [
        'phone_number',
        'type',
        'import_id',
    ];

    protected $guarded = [];

    protected $hidden = [];

    public function __construct($attributes = [], $exists = false)
    {
        parent::__construct($attributes, $exists);
    }

    public function phoneable()
    {
        return $this->morphTo();
    }
}
