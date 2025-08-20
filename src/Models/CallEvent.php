<?php

namespace ClarusSharedModels\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CallEvent extends Model
{
    use HasFactory, PreviousTimestampFormat;

    public static $rules = [
        'call_id' => 'required',
        'agent'   => 'required',
        'type'    => 'required',
    ];

    protected $fillable = ['call_id', 'agent', 'summary', 'type', 'old_value', 'new_value'];

    protected $hidden = ['call_id', 'user_id'];

    public function call()
    {
        return $this->belongsTo(Call::class);
    }
}
