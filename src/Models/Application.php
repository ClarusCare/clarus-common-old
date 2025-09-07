<?php

namespace ClarusCommon\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Application eloquent model.
 *
 * @author Erik Galloway <egalloway@claruscare.com>
 */
class Application extends Model
{
    use HasFactory;

    /**
     * Constant representing the Afterhours application.
     */
    public const AFTERHOURS = 'afterhours';

    /**
     * Constant representing the Daytime application.
     */
    public const DAYTIME = 'daytime';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['name', 'label'];

    /**
     * Get the Feature model relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function features()
    {
        return $this->belongsToMany(Feature::class, 'application_feature')
            ->using(ApplicationFeature::class)
            ->withTimestamps();
    }

    /**
     * Scope the query to only the Clarus After Hours application.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAfterhours(Builder $query)
    {
        return $query->where('name', static::AFTERHOURS);
    }

    /**
     * Scope the query to only the Clarus Daytime application.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDaytime(Builder $query)
    {
        return $query->where('name', static::DAYTIME);
    }
}
