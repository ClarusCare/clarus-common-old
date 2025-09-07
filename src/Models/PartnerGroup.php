<?php

namespace ClarusCommon\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * PartnerGroup eloquent model.
 *
 * @author Michael McDowell <mmcdowell@claruscare.com>
 * @author Daniel Picado <dpicado@claruscare.com>
 */
class PartnerGroup extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['stream_tenant_id', 'label'];

    /**
     * Get the Partner model relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function partners()
    {
        return $this->hasMany(Partner::class);
    }
}
