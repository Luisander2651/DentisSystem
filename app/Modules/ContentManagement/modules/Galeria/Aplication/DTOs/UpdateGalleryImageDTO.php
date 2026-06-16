<?php

declare(strict_types=1);

namespace App\Modules\ContentManagement\Modules\Galeria\Aplication\DTOs;

use App\Modules\ContentManagement\Modules\Galeria\Aplication\Exceptions\GalleryImageInputException;
use Illuminate\Http\UploadedFile;

final readonly class UpdateGalleryImageDTO
{
    public function __construct(
        public string $id,
        public ?string $description = null,
        public ?UploadedFile $image = null,
        public ?string $status = null,
    ) {
        if (empty($id)) {
            throw GalleryImageInputException::missingRequiredFields();
        }

        if (empty($description) && $image === null && empty($status)) {
            throw GalleryImageInputException::atLeastOneFieldRequired();
        }
    }
}