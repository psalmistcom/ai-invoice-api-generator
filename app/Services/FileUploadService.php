<?php

namespace App\Services;

use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Http\UploadedFile;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class FileUploadService
{
    public function storeImage(UploadedFile $file, string $directory): ?string
    {
        try {
            return Storage::putFile("public/{$directory}", $file);
        } catch (Exception $e) {
            Log::error('Image storage failed: ' . $e->getMessage());
            return null;
        }
    }

    public function storePdf(string $content, string $filename, string $directory): ?string
    {
        try {
            $path = "public/{$directory}/{$filename}";
            Storage::put($path, $content);
            return Storage::url($path);
        } catch (Exception $e) {
            Log::error('PDF storage failed: ' . $e->getMessage());
            return null;
        }
    }

    public function uploadImage(UploadedFile $file, string $folder): ?string
    {
        try {
            $response = Cloudinary::upload($file->getRealPath(), [
                'folder' => $folder,
                'resource_type' => 'image'
            ]);

            return $response->getSecurePath();
        } catch (Exception $e) {
            Log::error('File upload failed: ' . $e->getMessage());
            return null;
        }
    }

    public function uploadPdf(string $filePath, string $folder): ?string
    {
        try {
            $response = Cloudinary::upload($filePath, [
                'folder' => $folder,
                'resource_type' => 'auto'
            ]);

            return $response->getSecurePath();
        } catch (Exception $e) {
            Log::error('PDF upload failed: ' . $e->getMessage());
            return null;
        }
    }
}
