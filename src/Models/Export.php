<?php

namespace ClarusSharedModels\Models;

use App\Traits\AttachesS3Files;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Support\Facades\Storage;

/**
 * Export eloquent model.
 *
 * @author Erik Galloway <egalloway@claruscare.com>
 */
class Export extends Eloquent
{
    use AttachesS3Files, PreviousTimestampFormat;

    /**
     * The attachable S3 file storage definitions.
     *
     * @var array
     */
    protected $attachments = [
        'file' => [
            'storage_path' => 'files/:id/original',
            'keep_files'   => false,
        ],
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['name'];


    /**
     * Determine if the export file exists in storage.
     *
     * @param  string|null $disk
     * @return bool
     */
    public function fileExists($disk = null)
    {
        return Storage::disk($disk)->exists($this->getS3FilePath());
    }

    /**
     * Get the base file path of the export file in permanent storage.
     *
     * @return string
     */
    public function getFilePath(): string
    {
        return "files/{$this->id}/original";
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
     * Get the export file name used in permanent storage.
     *
     * @return string
     */
    public function getFileName(): string
    {
        return $this->file_file_name;
    }

    /**
     * Get the base directory path of the export file in permanent storage.
     *
     * @return string
     */
    public function getDirectoryPath(): string
    {
        return "files/{$this->id}";
    }
}
