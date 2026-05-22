<?php

declare(strict_types=1);

namespace App\Modules\ContentManagement\Modules\Galeria\Domain\Service;

use App\Modules\ContentManagement\Modules\Galeria\Domain\Entities\GalleryImageEntity;
use App\Modules\ContentManagement\Modules\Galeria\Domain\Repositories\GalleryImageRepositoryInterface;
use App\Modules\ContentManagement\Modules\Galeria\Domain\ValueObjects\GalleryImageId;
use App\Modules\ContentManagement\Modules\Galeria\Domain\ValueObjects\GalleryImageUrl;

final readonly class GalleryService
{
    public function __construct(
        private GalleryImageRepositoryInterface $repository,
    ) {
    }

    public function save(GalleryImageEntity $image): void
    {
        $this->repository->save($image);
    }

    public function deleteById(GalleryImageId $id): void
    {
        $this->repository->destroy($id);
    }

    public function getAllByUrlOrId(?GalleryImageId $id, ?GalleryImageUrl $url): array
    {
        return $this->repository->findByIdAndUrl(
            id: $id,
            url: $url,
        );
    }
}
