<?php

namespace App\Services;

use Cloudinary\Cloudinary;
use Illuminate\Http\UploadedFile;

class CloudinaryService
{
    private Cloudinary $client;

    public function __construct()
    {
        $this->client = new Cloudinary(env('CLOUDINARY_URL'));
    }

    public function upload(UploadedFile $file, string $folder): string
    {
        $result = $this->client->uploadApi()->upload($file->getRealPath(), [
            'folder' => $folder,
        ]);

        return $result['secure_url'];
    }

    public function delete(string $publicId): void
    {
        $this->client->uploadApi()->destroy($publicId);
    }
}
