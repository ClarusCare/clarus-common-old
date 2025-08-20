<?php

namespace ClarusSharedModels\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CallNote extends Model
{
    use HasFactory, PreviousTimestampFormat;

    public static $rules = [
        'call_id' => 'required',
        'user_id' => 'required',
        'text'    => 'required',
    ];

    public static $updateRules = [
        'text'                => 'required',
        'last_update_user_id' => 'required',
    ];

    protected $fillable = ['call_id', 'user_id', 'text', 'content', 'last_update_user_id', 'type'];

    protected $hidden = ['call_id', 'user_id', 'last_update_user_id'];

    /**
     * Get the Call model relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function call()
    {
        return $this->belongsTo(Call::class);
    }

    /**
     * Get the content attribute.
     *
     * @param  string  $value
     * @return void
     */
    public function getContentAttribute()
    {
        return $this->text;
    }

    public function getFormattedDateAttribute()
    {
        return $this->created_at->format('m/d/Y h:ia T');    }

    /**
     * Get the User model relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function lastUpdatedBy()
    {
        return $this->belongsTo(User::class, 'last_update_user_id');
    }

    public function lastUpdateUser()
    {
        return $this->belongsTo(User::class, 'last_update_user_id');
    }

    public function notedCall()
    {
        return $this->belongsTo(Call::class, 'call_id');
    }

    /**
     * Set the content attribute.
     *
     * @param  string  $value
     * @return void
     */
    public function setContentAttribute($value): void
    {
        $this->attributes['text'] = $value;
    }

    /**
     * Get the User model relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
