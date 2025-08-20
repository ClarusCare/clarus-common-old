<?php

namespace ClarusSharedModels\Models;

use EGALL\Listify\Listify;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CallDataElement extends Eloquent
{
    use HasFactory, Listify, PreviousTimestampFormat;

    public $inputTypes = [
        'checkbox'  => 'Checkbox',
        'date'      => 'Date',
        //        'datetime'  => 'Date/Time',
        'email'     => 'Email Address',
        'number'    => 'Number',
        'radio'     => 'Radio Buttons',
        //        'range'     => 'Range (Slider)',
        'select'    => 'Select/Drop-down',
        'tel'       => 'Telephone Number',
        'text'      => 'Text',
        'textarea'  => 'Text Box',
        'time'      => 'Time',
        'url'       => 'URL',
    ];

    protected $fillable = [
        'partner_id',
        'input_type',
        'name',
        'description',
        'input_maxlength',
        'input_max',
        'input_min',
        'input_step',
        'multiple',
        'required',
        'active',
    ];

    public function __construct(array $attributes = [], $exists = false)
    {
        parent::__construct($attributes, $exists);

        $this->getListifyConfig()->setScope($this->partner());
    }

    public function callDataElementOptions()
    {
        return $this->hasMany(CallDataElementOption::class);
    }

    public function createRules()
    {
        return [
            'name'                      => 'required',
            'input_type'                => 'required',
            'call_data_element_options' => 'required_if:input_type,select|required_if:input_type,radio',
        ];
    }

    public function getDataTypeAttribute()
    {
        //TODO: determine data type for number dynamically
        $dataTypes = [
            'checkbox'  => 'boolean',
            'date'      => 'date',
            'datetime'  => 'timestamp',
            'email'     => 'string',
            'number'    => 'decimal',
            'radio'     => 'string',
            'range'     => 'decimal',
            'select'    => 'array',
            'tel'       => 'string',
            'text'      => 'string',
            'textarea'  => 'text',
            'time'      => 'time',
            'url'       => 'string',
        ];

        return $dataTypes[$this->attributes['input_type']];
    }

    public function partner()
    {
        return $this->belongsTo(Partner::class);
    }

    public function updateRules()
    {
        return [
            'name'                      => 'required',
            'input_type'                => 'required',
            'call_data_element_options' => 'required_if:input_type,select|required_if:input_type,radio',
        ];
    }
}
