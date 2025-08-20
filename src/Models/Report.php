<?php

namespace ClarusSharedModels\Models;

use App\Traits\AttachesS3Files;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Support\Facades\Storage;

/**
 * Report eloquent model.
 *
 * @author Erik Galloway <egalloway@claruscare.com>
 */
class Report extends Eloquent
{
    use AttachesS3Files, PreviousTimestampFormat;

    /**
     * Constant representing the complete status.
     */
    public const STATUS_COMPLETE = 'complete';

    /**
     * Constant representing the failed status.
     */
    public const STATUS_FAILED = 'failed';

    /**
     * Constant representing the pending status.
     */
    public const STATUS_PENDING = 'pending';

    /**
     * Constant representing the processing status.
     */
    public const STATUS_PROCESSING = 'processing';

    /**
     * Constant representing the calls type.
     */
    public const TYPE_CALLS = 'calls';

    public static $rules = [
        'name' => 'required',
        'type' => 'required',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['output_url'];

    /**
     * The attachable S3 file storage definitions.
     *
     * @var array
     */
    protected $attachments = [
        'output' => [
            'storage_path' => 'outputs/:id/original',
            'keep_files'   => false,
        ],
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'parameters'           => 'array',
        'generated_by_partner' => 'integer',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'type', 'parameters', 'status', 'output', 'generated_by_partner'
    ];

    /**
     * Get the output URL attribute.
     *
     * @return string|void
     */
    public function getOutputUrlAttribute()
    {
        if (isset($this->attributes['output_file_name'])) {
            return $this->output->url;
        }
    }

    /**
     * Determine if the report file exists in storage.
     *
     * @param  string|null $disk
     * @return bool
     */
    public function fileExists($disk = null)
    {
        return Storage::disk($disk)->exists($this->getS3FilePath());
    }

    /**
     * Get the base file path of the report file in permanent storage.
     *
     * @return string
     */
    public function getFilePath(): string
    {
        return "outputs/{$this->id}/original";
    }

    /**
     * Get the S3 storage file path.
     *
     * @return string
     */
    public function getS3FilePath(): string
    {
        $path = $this->getFilePath();

        $fileName = $this->getFileName();

        return "{$path}/{$fileName}";
    }

    /**
     * Get the report file name used in permanent storage.
     *
     * @return string
     */
    public function getFileName(): string
    {
        return $this->output_file_name;
    }

    /**
     * Get the base directory path of the report file in permanent storage.
     *
     * @return string
     */
    public function getDirectoryPath(): string
    {
        return "outputs/{$this->id}";
    }

    /**
     * Scope a query to filter by partner ID.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $partnerId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByPartner($query, $partnerId)
    {
        return $query->where('generated_by_partner', $partnerId);
    }

    /**
     * Scope a query to filter by type.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $types
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByType($query, $types)
    {
        return $query->whereIn('type', $types);
    }

}
