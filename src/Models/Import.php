<?php

namespace ClarusCommon\Models;

use ClarusCommon\Traits\AttachesS3Files;
use Illuminate\Database\Eloquent\Model as Eloquent;

/**
 * Import eloquent model.
 *
 * @author Erik Galloway <egalloway@claruscare.com>
 */
class Import extends Eloquent
{
    use AttachesS3Files, PreviousTimestampFormat;

    /**
     * The attachable S3 file storage definitions.
     *
     * @var array
     */
    protected $attachments = [
        'data_file' => [
            'storage_path' => 'data_files/:id/original',
            'keep_files'   => false,
        ],
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'executed_by', 'filename', 'model', 'partner_id', 'status',
    ];

    /**
     * Get the Partner model relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function partner()
    {
        return $this->belongsTo(Partner::class);
    }

    /**
     * Get the Patient model relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function patients()
    {
        return $this->hasMany(Patient::class);
    }

    /**
     * Get the PhoneNumber model relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function phoneNumbers()
    {
        return $this->hasMany(PhoneNumber::class);
    }

    public static function rules($id = null)
    {
        return [];
    }
}
