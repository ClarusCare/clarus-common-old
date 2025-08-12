<?php

namespace App\Traits;

use stdClass;
use Exception;
use App\Http\Middleware\ValidatePresignedUrlSignature;
use App\Services\Interpolator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Attaches S3 files to models trait.
 *
 * @author Erik Galloway <egalloway@claruscare.com>
 */
trait AttachesS3Files
{
    /**
     * The attachments waiting to be uploaded to S3 when the model is saved.
     *
     * @var array
     */
    protected $pendingAttachmentFiles = [];

    /**
     * Update the attachment properties on the model record and store a file in S3.
     *
     * @param  string  $attachmentName
     * @param  \Illuminate\Http\UploadedFile|array  $file
     * @param  string|null  $fileName
     * @return mixed
     */
    public function attachFile($attachmentName, $file, $fileName = null)
    {
        if (! isset($this->attachments[$attachmentName])) {
            return false;
        }

        if ($file instanceof UploadedFile) {
            return $this->handleAttachingUploadedFile($file, $attachmentName, $fileName);
        }

        if ($this->isStreamedCloudUpload($file)) {
            $this->handleStreamedCloudUpload($file, $attachmentName);
        }
    }

    /**
     * Returns the filename for an attachment.
     *
     * @param  string  $attachmentName
     * @return string
     */
    public function attachmentFilename($attachmentName)
    {
        return $this->getAttribute("{$attachmentName}_file_name");
    }

    /**
     * Returns the S3 URL for an attachment.
     *
     * @param  string  $attachmentName
     * @return string
     */
    public function attachmentUrl($attachmentName)
    {
        if (! $this->attachmentFilename($attachmentName)) {
            return;
        }
       
        if (config('filesystems.disks.s3_private.enable_ec2_private_bucket')) {
            Log::info("File was uploaded to s3 private bucket using EC2 service, hence get playable audio url from s3 private bucket");
            if (!empty($attachmentName) && $this->isAttachmentBelongsToPrivateBucket($attachmentName) && config('filesystems.disks.s3_private.is_private_bucket')) {
                Log::info("Get presingned url for transfered files to s3 private bucket, response id : $this->id");
                $validateUrlInstance = new ValidatePresignedUrlSignature();
                return config('app.url')."/api/v4/resource-url?token=".$validateUrlInstance->generateHashKey("callResponses$this->id")."&type=callResponses&identifier=$this->id";
            }
        }

        $path = $this->resolvePath($attachmentName);
        
        Log::info("Get playable audio url from s3 public bucket");

        $fileName = $attachmentName == 'audio' ? rawurlencode($this->attachmentFilename($attachmentName)) : $this->attachmentFilename($attachmentName);
        return $this->disk()->url(
            $path.'/'.$fileName
        );
    }

    /**
     * Detach/delete all of a model's attached files.
     *
     * @return void
     */
    public function detachAllFiles(): void
    {
        foreach ($this->attachments as $name => $attachment) {
            $this->detachFile($name);
        }
    }

    /**
     * Detach/delete a model's attached file.
     *
     * @param  string  $attachmentName
     * @return bool
     */
    public function detachFile($attachmentName)
    {
        if (! isset($this->attachments[$attachmentName])) {
            return false;
        }

        $this->detachFromModel($attachmentName);

        $attachment = $this->attachments[$attachmentName];

        if ($attachment['keep_files'] == false) {
            $this->deleteFile($attachmentName);
        }

        return true;
    }

    /**
     * Force detach/delete all of a model's attached files.
     *
     * @return void
     */
    public function forceDetachAllFiles(): void
    {
        foreach ($this->attachments as $name => $attachment) {
            $this->forceDetachFile($name);
        }
    }

    /**
     * Force detach/delete a model's attached file.
     * Disregards 'keep_files' setting.
     *
     * @param  string  $attachmentName
     * @return bool
     */
    public function forceDetachFile($attachmentName)
    {
        if (isset($this->attachments[$attachmentName])) {
            $this->detachFromModel($attachmentName);

            $this->deleteFile($attachmentName);
        }
    }

    /**
     * Handle the dynamic retrieval of attachment objects.
     *
     * @param  string  $key
     * @return mixed
     */
    public function getAttribute($key)
    {
        if (is_array($this->attachments) && array_key_exists($key, $this->attachments)) {
            $attachment = new stdClass();
            $attachment->url = $this->attachmentUrl($key);
            $attachment->filename = $this->attachmentFilename($key);

            return $attachment;
        }

        return parent::getAttribute($key);
    }

    /**
     * Setup the model trait when initialized.
     *
     * @return void
     */
    public function initializeAttachesS3Files(): void
    {
        if (! property_exists($this, 'attachments')) {
            $this->attachments = [];
        }

        static::saved(function ($instance): void {
            foreach ($instance->pendingAttachmentFiles as $key => $file) {
                $instance->attachFile($key, $file);
            }

            $instance->pendingAttachmentFiles = [];
        });

        static::deleted(function ($instance): void {
            $instance->detachAllFiles();
        });
    }

    /**
     * Handle the dynamic setting of attachment objects.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return void
     */
    public function setAttribute($key, $value): void
    {
        if (is_array($this->attachments) && array_key_exists($key, $this->attachments)) {
            if ($value) {
                $this->pendingAttachmentFiles[$key] = $value;
            }

            return;
        }

        parent::setAttribute($key, $value);
    }

    /**
     * Updates all of the attachment properties on the model record.
     *
     * @param  string  $attachmentName
     * @param  array  $fileArray
     * @param  int|null  $fileSize
     * @return void
     */
    protected function attachArrayToModel($attachmentName, $fileArray, $fileSize = null): void
    {
        $propertyNames = $this->getPropertyNames($attachmentName);

        $this->{$propertyNames['file_name']} = $fileArray['name'];
        $this->{$propertyNames['file_size']} = $fileSize ?: null;
        $this->{$propertyNames['content_type']} = $fileArray['content_type'];
        $this->{$propertyNames['updated_at']} = now();

        $this->saveQuietly();
    }

    /**
     * Updates all of the attachment properties on the model record from an UploadedFile.
     *
     * @param  string  $attachmentName
     * @param  string  $fileName
     * @param  \Illuminate\Http\UploadedFile  $file
     * @return mixed
     */
    protected function attachUploadedFileToModel($attachmentName, $fileName, UploadedFile $file)
    {
        $propertyNames = $this->getPropertyNames($attachmentName);

        $this->{$propertyNames['file_name']} = $fileName;
        $this->{$propertyNames['file_size']} = $file->getSize();
        $this->{$propertyNames['content_type']} = $file->getMimeType();
        $this->{$propertyNames['updated_at']} = now();

        $this->saveQuietly();
    }

    /**
     * Deletes the attached file from S3 storage.
     *
     * @param  string  $attachmentName
     * @return void
     */
    protected function deleteFile($attachmentName): void
    {
        $path = $this->resolvePath($attachmentName);

        try {
            $this->disk()->delete($path);
        } catch (Exception $e) {
            Log::warning("Failed to delete S3 file: {$path}");
        }
    }

    /**
     * Nullify all of the attachment properties on the model record.
     *
     * @param  string  $attachmentName
     * @return $this
     */
    protected function detachFromModel($attachmentName)
    {
        $propertyNames = $this->getPropertyNames($attachmentName);

        $this->{$propertyNames['file_name']} = null;
        $this->{$propertyNames['file_size']} = null;
        $this->{$propertyNames['content_type']} = null;
        $this->{$propertyNames['updated_at']} = null;

        return $this->save();
    }

    /**
     * Get the storage disk used to manage the files.
     *
     * @return \League\Flysystem\AwsS3v3\AwsS3Adapter
     */
    protected function disk()
    {
        return Storage::disk('s3');
    }

    /**
     * Get the storage disk used to manage the files.
     *
     * @return \League\Flysystem\AwsS3v3\AwsS3Adapter
     */
    protected function privateDisk()
    {
        return Storage::disk('s3_private');
    }

    /**
     * Returns an array of property names for an attachment.
     * Names correspond to columns in the model's database table.
     *
     * @param  string  $attachmentName
     * @return array
     */
    protected function getPropertyNames($attachmentName)
    {
        return [
            'file_name'     => "{$attachmentName}_file_name",
            'file_size'     => "{$attachmentName}_file_size",
            'content_type'  => "{$attachmentName}_content_type",
            'updated_at'    => "{$attachmentName}_updated_at",
        ];
    }

    /**
     * Handle attaching an UploadedFile instance to the model.
     *
     * @param  \Illuminate\Http\UploadedFile  $file
     * @param  string  $attachmentName
     * @param  string  $fileName
     * @return string|false
     */
    protected function handleAttachingUploadedFile(UploadedFile $file, $attachmentName, $fileName)
    {
        $path = $this->resolvePath($attachmentName);

        $fileName = $fileName ?: $file->getClientOriginalName();

        $this->attachUploadedFileToModel($attachmentName, $fileName, $file);

        if ($this->isAttachmentBelongsToPrivateBucket($attachmentName)) {
            if (config('filesystems.disks.s3.sync_public_bucket')) {
                Log::info("Upload file to public bucket as well when sync public bucket flag is enabled, file name : $path/$fileName");
                $this->disk()->putFileAs($path, $file, $fileName, 'public');
            }
            return $this->privateDisk()->putFileAs($path, $file, $fileName);
        }

        return $this->disk()->putFileAs($path, $file, $fileName, 'public');
    }

    /**
     * Handle a streamed file upload to the cloud.
     *
     * @param  array  $file
     * @param  string  $attachmentName
     * @return void
     */
    protected function handleStreamedCloudUpload($file, $attachmentName): void
    {
        $attachmentPath = $this->resolvePath($attachmentName);

        $filePath = $attachmentPath.'/'.$file['name'];

        if ($this->disk()->exists($filePath)) {
            $this->disk()->delete($filePath);
        }

        $this->disk()->copy($file['key'], $filePath);

        $this->disk()->setVisibility($filePath, 'public');

        $this->attachArrayToModel($attachmentName, $file, $this->disk()->size($filePath));
    }

    /**
     * Determine if the given file is a streamed file upload to cloud storage.
     *
     * @param  array  $file
     * @return bool
     */
    protected function isStreamedCloudUpload($file): bool
    {
        return is_array($file) && array_key_exists('key', $file);
    }

    /**
     * Resolve full interpolated attachment file path.
     *
     * @param  string  $attachmentName
     * @return bool|string
     */
    protected function resolvePath($attachmentName)
    {
        if (! isset($this->attachments[$attachmentName])) {
            return false;
        }

        $storagePath = $this->attachments[$attachmentName]['storage_path'];

        $propertyNames = $this->getPropertyNames($attachmentName);

        return (new Interpolator)->interpolate($storagePath, $propertyNames, $this);
    }

    /**
     * Determine if the given attachment belongs to private bucket.
     *
     * @param  string  $attachmentName
     * @return bool
     */
    protected function isAttachmentBelongsToPrivateBucket($attachmentName): bool
    {
        return $attachmentName == 'recording_to_patient';
    }
}
