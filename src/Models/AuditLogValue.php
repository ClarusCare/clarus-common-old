<?php

namespace ClarusSharedModels\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Audit log value eloquent model.
 *
 * @author Erik Galloway <egalloway@claruscare.com>
 */
class AuditLogValue extends Model
{
    use HasFactory;

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = ['value' => 'array'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['audit_log_id', 'value_type', 'value'];

    /**
     * Get the AuditLog model relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function auditLog()
    {
        return $this->belongsTo(AuditLog::class);
    }
}
