<?php

namespace ClarusSharedModels\Models;

use App\Scopes\OrderByPosition;
use Illuminate\Database\Eloquent\Model;

/**
 * CalendarDay eloquent model.
 *
 * @author Michael McDowell <mmcdowell@claruscare.com>
 */
class CalendarDay extends Model
{
    /**
     * Constant representing the calendar day name.
     */
    public const FRIDAY = 'friday';

    /**
     * Constant representing the calendar day name.
     */
    public const MONDAY = 'monday';

    /**
     * Constant representing the calendar day name.
     */
    public const SATURDAY = 'saturday';

    /**
     * Constant representing the calendar day name.
     */
    public const SUNDAY = 'sunday';

    /**
     * Constant representing the calendar day name.
     */
    public const THURSDAY = 'thursday';

    /**
     * Constant representing the calendar day name.
     */
    public const TUESDAY = 'tuesday';

    /**
     * Constant representing the calendar day name.
     */
    public const WEDNESDAY = 'wednesday';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The "type" of the primary key ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'name';

    /*
     * Perform any actions required after the model boots.
     *
     * @return void
     */
    protected static function booted(): void
    {
        static::addGlobalScope(new OrderByPosition);
    }
}
