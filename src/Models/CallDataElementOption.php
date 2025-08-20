<?php

namespace ClarusSharedModels\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;

class CallDataElementOption extends Eloquent
{
    use PreviousTimestampFormat;

    protected $fillable = [
        'slug',
        'value',
        'call_data_element_id',
    ];

    public function callDataElement()
    {
        return $this->belongsTo(CallDataElement::class);
    }

    public function createRules()
    {
        return [
            'slug'  => 'required',
            'value' => 'required',
        ];
    }

    public function updateRules()
    {
        return [
            'slug'  => 'required',
            'value' => 'required',
        ];
    }
}
