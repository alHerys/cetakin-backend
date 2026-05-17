<?php

namespace App\Services;

use Cloudinary\Cloudinary;
use Illuminate\Http\UploadedFile;

class CloudinaryService
{
    private ?Cloudinary $client = null;

    private function client(): Cloudinary
    {
        if (!$this->client) {
            $this->client = new Cloudinary(env('CLOUDINARY_URL'));
        }

        return $this->client;
    }

    public function upload(UploadedFile $file, string $folder): string
    {
        $result = $this->client()->uploadApi()->upload($file->getRealPath(), [
            'folder' => $folder,
        ]);

        return $result['secure_url'];
    }

    public function delete(string $publicId): void
    {
        $this->client()->uploadApi()->destroy($publicId);
    }
}
