<?php

namespace ClarusSharedModels\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Base enumerable eloquent model.
 *
 * @author Daniel Picado <dpicado@claruscare.com>
 */
abstract class Enumerable extends Model
{
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
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'name';
}
