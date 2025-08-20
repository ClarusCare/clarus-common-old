<?php

namespace ClarusSharedModels\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CallAutomatedTranscriptionStatus extends Model
{
    use HasFactory;

    public $table = 'call_automated_transcription_status';

    /**
     * Get the Call model relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToOne
     */
    public function call()
    {
        return $this->belongsTo(Call::class);
    }

    public static $rules = [
        'call_id'    => 'required',
    ];

    protected $fillable = ['call_id', 'total_recording_count', 'total_transcription_count'];

    protected $hidden = ['call_id'];
}
