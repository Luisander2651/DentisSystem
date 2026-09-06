<?php

declare(strict_types=1);

namespace App\Modules\ContentManagement\Modules\Galeria\Domain\Service;

use App\Modules\ContentManagement\Modules\Galeria\Domain\Entities\GalleryImageEntity;
use App\Modules\ContentManagement\Modules\Galeria\Domain\Repositories\GalleryImageRepositoryInterface;
use App\Modules\ContentManagement\Modules\Galeria\Domain\ValueObjects\GalleryImageId;
use App\Modules\ContentManagement\Modules\Galeria\Domain\ValueObjects\GalleryImageUrl;
use App\Modules\ContentManagement\Modules\Galeria\Domain\ValueObjects\GalleryStatus;

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

    public function getAllByUrlOrId(?GalleryImageId $id, ?GalleryImageUrl $url, ?GalleryStatus $status): array
    {
        return $this->repository->findByIdAndUrlAndStatus(
            id: $id,
            url: $url,
            status: $status
        );
    }
}
