<?php

declare(strict_types=1);

namespace App\Modules\ContentManagement\Modules\Galeria\Aplication\DTOs;

use App\Modules\ContentManagement\Modules\Galeria\Aplication\Exceptions\GalleryImageInputException;

final readonly class DeleteGalleryImageDTO
{
    public function __construct(
        public string $id,
    ) {
        if (empty($id)) {
            throw GalleryImageInputException::missingRequiredFields();
        }
    }
}