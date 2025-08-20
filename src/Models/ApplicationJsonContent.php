<?php

namespace ClarusSharedModels\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApplicationJsonContent extends Model
{
    use HasFactory;

    protected $table = 'application_json_content';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'partner_id', 'type', 'subtype', 'content', 'reference_id'
    ];
}
