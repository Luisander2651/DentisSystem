<?php

namespace App\Modules\ContentManagement\Modules\Galeria\Domain\Repositories;

use App\Core\Domain\Pagination\Page;
use App\Core\Domain\Pagination\PageRequest;
use App\Modules\ContentManagement\Modules\Galeria\Domain\Entities\GalleryImageEntity;
use App\Modules\ContentManagement\Modules\Galeria\Domain\ValueObjects\GalleryImageId;
use App\Modules\ContentManagement\Modules\Galeria\Domain\ValueObjects\GalleryImageUrl;
use App\Modules\ContentManagement\Modules\Galeria\Domain\ValueObjects\GalleryStatus;

interface GalleryImageRepositoryInterface
{
    public function save(GalleryImageEntity $data): void;

    public function destroy(GalleryImageId $id): void;

    public function findByIdAndUrlAndStatus(?GalleryImageId $id, ?GalleryImageUrl $url, ?GalleryStatus $status): array;

    /**
     * Spec 028 (CA7): one page of the gallery of the panel, newest first.
     *
     * @return Page<GalleryImageEntity>
     */
    public function findPage(PageRequest $request): Page;

    /**
     * Spec 028 (CA5): how many images the whole gallery holds, by status.
     *
     * @return array{total: int, visible: int, hidden: int}
     */
    public function countByStatus(): array;
}
