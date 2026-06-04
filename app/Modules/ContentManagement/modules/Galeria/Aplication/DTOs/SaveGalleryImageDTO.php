<?php

declare(strict_types=1);

namespace App\Modules\ContentManagement\Modules\Galeria\Aplication\DTOs;

use App\Modules\ContentManagement\Modules\Galeria\Aplication\Exceptions\GalleryImageInputException;
use Illuminate\Http\UploadedFile;

final readonly class SaveGalleryImageDTO
{
    public function __construct(
        public string $description,
        public UploadedFile $image,
    ) {
        if (empty($description) || empty($image)) {
            throw GalleryImageInputException::missingRequiredFields();
        }
    }
}