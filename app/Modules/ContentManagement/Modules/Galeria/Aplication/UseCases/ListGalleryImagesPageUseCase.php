<?php

declare(strict_types=1);

namespace App\Modules\ContentManagement\Modules\Galeria\Aplication\UseCases;

use App\Core\Authorization\AuthorizationServiceInterface;
use App\Core\Domain\Pagination\Page;
use App\Core\Domain\Pagination\PageRequest;
use App\Modules\ContentManagement\Modules\Galeria\Domain\Entities\GalleryImageEntity;
use App\Modules\ContentManagement\Modules\Galeria\Domain\Service\GalleryService;

/**
 * Spec 028 (CA5, CA14; P5, A21): one page of the gallery of the panel with the counts by status of
 * the whole list, only for whoever manages the gallery.
 */
final readonly class ListGalleryImagesPageUseCase
{
    public function __construct(
        private GalleryService $service,
        private AuthorizationServiceInterface $authorization,
    ) {}

    /**
     * @return array{page: Page<GalleryImageEntity>, counts: array{total: int, visible: int, hidden: int}}
     */
    public function execute(PageRequest $request): array
    {
        $this->authorization->assertCan('manage.gallery');

        return [
            'page' => $this->service->findPage($request),
            'counts' => $this->service->countByStatus(),
        ];
    }
}
