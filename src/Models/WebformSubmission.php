<?php

namespace ClarusSharedModels\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebformSubmission extends Model
{
    use HasFactory;
    protected $fillable = ['partner_id', 'webform_id', 'data', 'sid'];
    
    protected $primaryKey = 'sid';

}
