<?php

namespace App\Models\OAuth;

use App\Models\PreviousTimestampFormat;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use PreviousTimestampFormat;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['name'];

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'oauth_clients';
}
