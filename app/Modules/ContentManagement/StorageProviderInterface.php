<?php

declare(strict_types=1);

namespace App\Modules\ContentManagement;

use Illuminate\Http\UploadedFile;

interface StorageProviderInterface
{
    public static function new(string $moduleName, string $storagePath): self;

    public function saveImage(UploadedFile $image): string;

    public function updateImage(string $currentImagePath, UploadedFile $newImage): string;

    public function deleteImage(string $imagePath): void;
}