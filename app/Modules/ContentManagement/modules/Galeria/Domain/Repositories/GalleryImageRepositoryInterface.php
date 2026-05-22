<?php

namespace App\Modules\ContentManagement\Modules\Galeria\Domain\Repositories;

use App\Modules\ContentManagement\Modules\Galeria\Domain\Entities\GalleryImageEntity;
use App\Modules\ContentManagement\Modules\Galeria\Domain\ValueObjects\GalleryImageId;
use App\Modules\ContentManagement\Modules\Galeria\Domain\ValueObjects\GalleryImageUrl;

interface GalleryImageRepositoryInterface
{
    public function save(GalleryImageEntity $data): void;

    public function destroy(GalleryImageId $id): void;

    public function findByIdAndUrl(?GalleryImageId $id, ?GalleryImageUrl $url): array;
}