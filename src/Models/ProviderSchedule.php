<?php

namespace ClarusSharedModels\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;

class ProviderSchedule extends Eloquent
{
    use PreviousTimestampFormat;

    public static $rules = [];

    protected $fillable = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

    protected $guarded = [];

    public function provider()
    {
        return $this->belongsTo(\App\Models\Provider::class);
    }
}
