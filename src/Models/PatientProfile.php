<?php

namespace ClarusSharedModels\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PatientProfile extends Model
{
    use HasFactory;

    protected $fillable = ['phone_number', 'metadata'];

    public function scopeFindByPhoneNumber($query, $phoneNumber)
    {
        return $query->where('phone_number', 'like', '%' . $phoneNumber);
    }

}