<?php

namespace App\Modules\ContentManagement;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Filesystem\FilesystemAdapter;

final class StorageProvider
{
    private const DEFAULT_DISK = 'public';

    public function __construct(
        public string $moduleName,
        public string $storagePath,
    ) {
        if (trim($moduleName) === '') {
            throw new \InvalidArgumentException('Module name cannot be empty.');
        }

        if (trim($storagePath) === '') {
            throw new \InvalidArgumentException('Storage path cannot be empty.');
        }

        $this->storagePath = rtrim($storagePath, '/');
    }

    public static function new(string $moduleName, string $storagePath): self
    {
        return new self($moduleName, $storagePath);
    }

    public function saveImage(Request $request): string
    {
        $file = $request->file('image');
        if (! $file instanceof UploadedFile || ! $file->isValid()) {
            throw new \InvalidArgumentException('No valid image uploaded.');
        }

        $this->verifyImageExtension($file->getClientOriginalExtension(), $file->getMimeType());

        $extension = strtolower($file->getClientOriginalExtension());
        $filename = $this->generateFilename($extension);

        $directory = trim($this->storagePath, '/') . '/' . trim($this->moduleName, '/');
        $storedPath = Storage::disk(self::DEFAULT_DISK)->putFileAs($directory, $file, $filename);

        if ($storedPath === false) {
            throw new \RuntimeException('Failed to store uploaded image.');
        }

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk(self::DEFAULT_DISK);

        return $disk->url($storedPath);
    }

    public function deleteImage(string $imagePath): void
    {
        $relative = ltrim(preg_replace('#^/storage/#', '', $imagePath), '/');
        Storage::disk(self::DEFAULT_DISK)->delete($relative);
    }

    private function verifyImageExtension(string $extension, ?string $mime = null): void
    {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif'];
        if (! in_array(strtolower($extension), $allowed, true)) {
            throw new \InvalidArgumentException('Unsupported image extension.');
        }

        if ($mime !== null && ! str_starts_with($mime, 'image/')) {
            throw new \InvalidArgumentException('Uploaded file is not an image.');
        }
    }

    private function generateFilename(string $extension): string
    {
        return Str::uuid()->toString() . '.' . $extension;
    }
}