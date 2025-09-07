<?php

namespace ClarusCommon\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Event request status eloquent model.
 *
 * @method \Illuminate\Database\Eloquent\Builder accepted()
 * @method static \Illuminate\Database\Eloquent\Builder accepted()
 * @method \Illuminate\Database\Eloquent\Builder canceled()
 * @method static \Illuminate\Database\Eloquent\Builder canceled()
 * @method \Illuminate\Database\Eloquent\Builder declined()
 * @method static \Illuminate\Database\Eloquent\Builder declined()
 * @method \Illuminate\Database\Eloquent\Builder expired()
 * @method static \Illuminate\Database\Eloquent\Builder expired()
 * @method \Illuminate\Database\Eloquent\Builder forwarded()
 * @method static \Illuminate\Database\Eloquent\Builder forwarded()
 * @method \Illuminate\Database\Eloquent\Builder pending()
 * @method static \Illuminate\Database\Eloquent\Builder pending()
 * @method \Illuminate\Database\Eloquent\Builder sent()
 * @method static \Illuminate\Database\Eloquent\Builder sent()
 */
class EventRequestStatus extends Model
{
    use HasFactory;

    /**
     * Constant representing an accept event request status.
     */
    public const ACCEPTED = 'accepted';

    /**
     * Constant representing an accept event request status.
     */
    public const CANCELED = 'canceled';

    /**
     * Constant representing a declined event request status.
     */
    public const DECLINED = 'declined';

    /**
     * Constant representing an expired event request status.
     */
    public const EXPIRED = 'expired';

    /**
     * Constant representing a forwarded event request status.
     */
    public const FORWARDED = 'forwarded';

    /**
     * Constant representing a pending event request status.
     */
    public const PENDING = 'pending';

    /**
     * Constant representing a sent event request status.
     */
    public const SENT = 'sent';

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = ['is_complete' => 'boolean'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['name', 'is_complete'];

    /**
     * Determine if the status is a final status that does not change.
     *
     * @return bool
     */
    public function isComplete(): bool
    {
        return $this->is_complete;
    }

    /**
     * Scope the query to only the "accepted" status.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAccepted(Builder $query)
    {
        return $query->where('name', static::ACCEPTED);
    }

    /**
     * Scope the query to only the active statuses (accepted, pending, sent).
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive(Builder $query)
    {
        return $query->where(function ($query): void {
            $query->whereIn('name', [
                static::PENDING,
                static::SENT,
                static::ACCEPTED,
                static::FORWARDED,
            ]);
        });
    }

    /**
     * Scope the query to only the "canceled" status.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCanceled(Builder $query)
    {
        return $query->where('name', static::CANCELED);
    }

    /**
     * Scope the query to only the "declined" status.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDeclined(Builder $query)
    {
        return $query->where('name', static::DECLINED);
    }

    /**
     * Scope the query to only the "expired" status.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeExpired(Builder $query)
    {
        return $query->where('name', static::EXPIRED);
    }

    /**
     * Scope the query to only the "forwarded" status.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForwarded(Builder $query)
    {
        return $query->where('name', static::FORWARDED);
    }

    /**
     * Scope the query to only incomplete statuses.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeIncomplete(Builder $query)
    {
        return $query->where('is_complete', false);
    }

    /**
     * Scope the query to only the "pending" status.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePending(Builder $query)
    {
        return $query->where('name', static::PENDING);
    }

    /**
     * Scope the query to only the "sent" status.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSent(Builder $query)
    {
        return $query->where('name', static::SENT);
    }
}
