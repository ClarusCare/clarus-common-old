<?php

namespace ClarusSharedModels\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CallDatum extends Eloquent
{
    use HasFactory, PreviousTimestampFormat;

    protected $appends = ['value', 'call_sid'];

    protected $fillable = [
        'call_id',
        'call_data_element_id',
        'call_data_element_name',
        'data_type',
        'value_array',
        'value_bigint',
        'value_boolean',
        'value_date',
        'value_decimal',
        'value_int',
        'value_string',
        'value_text',
        'value_time',
        'value_timestamp',
        'last_update_user_id',
    ];

    protected $guarded = [
        'value_array',
        'value_bigint',
        'value_boolean',
        'value_date',
        'value_decimal',
        'value_int',
        'value_string',
        'value_text',
        'value_time',
        'value_timestamp',
    ];

    protected $hidden = [
        'call_id',
        'value_array',
        'value_bigint',
        'value_boolean',
        'value_date',
        'value_decimal',
        'value_int',
        'value_string',
        'value_text',
        'value_time',
        'value_timestamp',
        'call',
    ];

    public function call()
    {
        return $this->belongsTo(Call::class);
    }

    public function callDataElement()
    {
        return $this->belongsTo(CallDataElement::class);
    }

    public function createRules($dataType)
    {
        return [
            'call_data_element_id'   => 'required',
            'call_data_element_name' => 'required',
            'data_type'              => 'required',
            'last_update_user_id'    => 'required',
            'value'                  => $this->getValueValidationString($dataType),
        ];
    }

    public function getCallSidAttribute()
    {
        return $this->call->sid;
    }

    public function getValueAttribute()
    {
        $dataType = ($this->attributes['data_type'] ?? null);

        if (! $dataType) {
            return;
        }

        $valueColumn = 'value_'.$dataType;

        $value = ($this->attributes[$valueColumn] ?? '');

        return $dataType == 'array' ? json_decode($value) : $value;
    }

    public function lastUpdateUser()
    {
        return $this->belongsTo(User::class, 'last_update_user_id');
    }

    public function updateRules($dataType)
    {
        return [
            'last_update_user_id' => 'required',
            'value'               => $this->getValueValidationString($dataType),
        ];
    }

    protected function getValueValidationString($dataType)
    {
        switch ($dataType) {
            case 'array':
                $valueValidation = 'required|array';

                break;
            case 'bigint':
                $valueValidation = 'required|integer';

                break;
            case 'boolean':
                $valueValidation = 'required|boolean';

                break;
            case 'date':
                $valueValidation = 'required|date';

                break;
            case 'decimal':
                $valueValidation = 'required|numeric';

                break;
            case 'int':
                $valueValidation = 'required|integer';

                break;
            case 'string':
                $valueValidation = 'required|string';

                break;
            case 'text':
                $valueValidation = 'required|string';

                break;
            case 'time':
                $valueValidation = 'required|string';

                break;
            case 'timestamp':
                $valueValidation = 'required|date';

                break;
            default:
                $valueValidation = 'required';

                break;
        }

        return $valueValidation;
    }
}
