<?php

namespace App\Traits;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

trait ManagesStorage
{
    // Store an uploaded file and return its path.
    public function storeFile(UploadedFile $file, ?string $directory = null): string
    {
        $directory = $directory ? config("filesystems.storage.directories.{$directory}", $directory) : 'uploads';
        $path = $file->store($directory);
        if ($path === false) {
            throw new RuntimeException('Unable to store the uploaded file.');
        }

        return $path;
    }

    // Get a file URL or return the fallback URL.
    public function getImageUrl(?string $path = null, ?string $fallback = null): ?string
    {
        if (blank($path)) {
            return $fallback;
        }

        // Absolute URL: email clients cannot load relative image paths.
        return Storage::exists($path) ? asset(Storage::url($path)) : $fallback;
    }

    // Delete a stored file if it exists.
    public function deleteStorageFile(?string $path): bool
    {
        if (blank($path)) {
            return false;
        }

        return Storage::exists($path) && Storage::delete($path);
    }
}
