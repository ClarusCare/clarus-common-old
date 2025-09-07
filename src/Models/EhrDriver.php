<?php

namespace ClarusCommon\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EhrDriver extends Model
{
    use HasFactory;

    public static $rules = [
        'name'     => 'required',
        'endpoint' => 'required',
    ];

    protected $fillable = ['name', 'internal_name', 'version', 'endpoint', 'metadata'];

    public function partnerEhrSetting()
    {
        return $this->belongsTo(PartnerEhrSetting::class);
    }
}
