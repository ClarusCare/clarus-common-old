<?php

namespace ClarusSharedModels\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * ApplicationConfigurations Eloquent model.
 * 
 * Provides access to application configuration values stored in the database.
 * 
 * @author Shyam Prajapat
 */
class ApplicationConfigurations extends Model
{
    use HasFactory;

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'internal_name',
        'value',
    ];

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'application_configurations';

    /**
     * Get configuration value by internal name.
     *
     * @param string $name
     * @return string|null
     */
    public static function getConfigurationValueByName(string $name): ?string
    {
        $row = self::where('internal_name', $name)->first();
        return $row ? $row->value : null;
    }
}
