<?php

namespace ClarusSharedModels\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScriptScheduler extends Model
{
    use HasFactory;
    protected $table = 'script_scheduler'; 

    protected $fillable = [
        'script_name',
        'status',
    ];

}
