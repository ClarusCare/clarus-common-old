<?php

namespace ClarusCommon\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class callQualityReport extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['partner_id', 'partner_name', 'start_date', 'end_date', 'viewType', 'data', 'summary_report', 'pdf', 'linked_reports', 'linked_partners'];

}
