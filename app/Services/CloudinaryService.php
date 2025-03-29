<?php

namespace App\Services;

use Cloudinary\Cloudinary;
use Cloudinary\Api\Upload\UploadApi;
use Exception;

class CloudinaryService
{
    protected $cloudinary;

    public function __construct()
    {
        $this->cloudinary = new Cloudinary([
            'cloud' => [
                'cloud_name' => env('CLOUDINARY_CLOUD_NAME'),
                'api_key'    => env('CLOUDINARY_API_KEY'),
                'api_secret' => env('CLOUDINARY_API_SECRET'),
            ],
        ]);
    }

    /**
     * Upload a file to Cloudinary.
     *
     * @param string $filePath The path to the file to upload.
     * @param string $folder The folder to upload the file to (optional).
     * @param string $resourceType The type of resource (e.g., 'raw', 'image').
     * @return array The Cloudinary upload response.
     * @throws Exception If the upload fails.
     */
    public function uploadFile($filePath, $folder = '', $resourceType = 'auto')
    {
        try {
            return $this->cloudinary->uploadApi()->upload($filePath, [
                'folder' => $folder,
                'resource_type' => $resourceType,
            ]);
        } catch (Exception $e) {
            throw new Exception("Failed to upload file to Cloudinary: " . $e->getMessage());
        }
    }

    /**
     * Get the secure URL of an uploaded file.
     *
     * @param string $publicId The public ID of the file.
     * @return string The secure URL.
     */
    public function getFileUrl($publicId)
    {
        return $this->cloudinary->image($publicId)->toUrl();
    }
}
