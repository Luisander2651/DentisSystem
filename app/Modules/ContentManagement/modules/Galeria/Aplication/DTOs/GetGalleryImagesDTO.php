<?php

declare(strict_types=1);

namespace App\Modules\ContentManagement\Modules\Galeria\Aplication\DTOs;

final readonly class GetGalleryImagesDTO
{
    public function __construct(
        public ?string $id = null,
        public ?string $url = null,
        public ?string $status = null,
    ) {}
}