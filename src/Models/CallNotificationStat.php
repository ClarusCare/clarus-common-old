<?php

namespace ClarusSharedModels\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CallNotificationStat extends Model
{
    use HasFactory, PreviousTimestampFormat;

    protected $table = 'call_notification_stats';

    protected $fillable = [
        'call_id',
        'type', 
        'counts',
    ];



public static function updateOrCreateStats($call)
{
    // Check if a record already exists for the given call_id and type
    $record = self::where([
        ['call_id', $call->id],
        ['type', 'fallback-notification']
    ])->first();

    if ($record) {
        // If a record exists, increment the counts
        $record->counts++;
        $record->save(); // Save the updated record
    } else {
        // If no record exists, create a new one with an initial count of 1
        $record = self::create([
            'call_id' => $call->id,
            'type' => 'fallback-notification',
            'counts' => 1
        ]);
    }

    return $record; // Return the updated or created record
}


}