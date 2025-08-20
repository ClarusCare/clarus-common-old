<?php

namespace ClarusSharedModels\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CallPatientDetails extends Model
{
    use HasFactory;

    public function call()
    {
        return $this->belongsTo(Call::class);
    }

    public static $rules = [
        'call_id'    => 'required',
        'first_name' => 'required',
    ];

    protected $fillable = ['call_id', 'first_name', 'middle_name', 'last_name', 'gender', 'mrn', 'additional_info'];

    protected $hidden = ['call_id'];

}
