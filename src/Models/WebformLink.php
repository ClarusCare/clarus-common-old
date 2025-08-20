<?php

namespace ClarusSharedModels\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WebformLink extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'partner_id', 'sid', 'webform_id'];

    protected $baseURLS = [
        'local'         => "http://localhost:3000/",
        'staging'       => "https://message.staging.clarus-care.com/",
        'production'    => "https://message.claruscare.com/",
    ];

    public function getPublicURL () {
        return $this->baseURLS[env('APP_ENV')]. $this->sid;
    }

    public function webform () {
        return $this->hasOne( 'App\Models\Webform', 'id', 'webform_id' );
    }

}
