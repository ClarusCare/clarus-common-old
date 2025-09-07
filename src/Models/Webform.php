<?php

namespace ClarusCommon\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Webform extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'form', 'is_active', 'partner_id', 'copied_from', 'copied_at'];
}
