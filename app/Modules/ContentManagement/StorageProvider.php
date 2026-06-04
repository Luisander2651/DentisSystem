<?php

namespace App\Modules\ContentManagement;

use App\Modules\ContentManagement\Domain\Exceptions\StorageException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Filesystem\FilesystemAdapter;


final class StorageProvider implements StorageProviderInterface
{
    private const DEFAULT_DISK = 'public';

    public function __construct(
        public string $moduleName,
        public string $storagePath,
    ) {
        if (trim($moduleName) === '') {
            throw StorageException::emptyModuleName();
        }

        if (trim($storagePath) === '') {
            throw StorageException::emptyStoragePath();
        }

        $this->storagePath = rtrim($storagePath, '/');
    }

    public static function new(string $moduleName, string $storagePath): self
    {
        return new self($moduleName, $storagePath);
    }

    public function saveImage(UploadedFile $image): string
    {
        if (! $image instanceof UploadedFile || ! $image->isValid()) {
            throw StorageException::invalidUploadedImage();
        }

        $this->verifyImageExtension($image->getClientOriginalExtension(), $image->getMimeType());

        $extension = strtolower($image->getClientOriginalExtension());
        $filename = $this->generateFilename($extension);

        $directory = trim($this->storagePath, '/') . '/' . trim($this->moduleName, '/');
        $storedPath = Storage::disk(self::DEFAULT_DISK)->putFileAs($directory, $image, $filename);

        if ($storedPath === false) {
            throw StorageException::storeFailed();
        }

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk(self::DEFAULT_DISK);

        return $disk->url($storedPath);
    }

    public function updateImage(string $currentImagePath, UploadedFile $newImage): string
    {
        $this->deleteImage($currentImagePath);
        return $this->saveImage($newImage);
    }

    public function deleteImage(string $imagePath): void
    {
        $relativePath = $this->normalizeStoredImagePath($imagePath);

        if ($relativePath === '') {
            return;
        }

        Storage::disk(self::DEFAULT_DISK)->delete($relativePath);
    }

    private function normalizeStoredImagePath(string $imagePath): string
    {
        $trimmedPath = trim($imagePath);

        if ($trimmedPath === '') {
            return '';
        }

        $path = parse_url($trimmedPath, PHP_URL_PATH);
        if (! is_string($path) || $path === '') {
            $path = $trimmedPath;
        }

        $path = ltrim($path, '/');

        if (str_starts_with($path, 'storage/')) {
            return substr($path, strlen('storage/'));
        }

        return $path;
    }

    private function verifyImageExtension(string $extension, ?string $mime = null): void
    {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif'];
        if (! in_array(strtolower($extension), $allowed, true)) {
            throw StorageException::unsupportedImageExtension();
        }

        if ($mime !== null && ! str_starts_with($mime, 'image/')) {
            throw StorageException::invalidImageMime();
        }
    }

    private function generateFilename(string $extension): string
    {
        return Str::uuid()->toString() . '.' . $extension;
    }
}