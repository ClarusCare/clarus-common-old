<?php

namespace ClarusCommon\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PointOfContact extends Model
{
    use HasFactory, PreviousTimestampFormat;

    protected $fillable = ['user_id', 'provider_id', 'description'];

    protected $table = 'points_of_contact';

    public function provider()
    {
        return $this->belongsTo(Provider::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
