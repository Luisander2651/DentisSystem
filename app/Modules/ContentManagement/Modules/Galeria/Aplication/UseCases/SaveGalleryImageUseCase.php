<?php

declare(strict_types=1);

namespace App\Modules\ContentManagement\Modules\Galeria\Aplication\UseCases;

use App\Core\Authorization\AuthorizationServiceInterface;
use App\Modules\ContentManagement\Modules\Galeria\Aplication\DTOs\SaveGalleryImageDTO;
use App\Modules\ContentManagement\Modules\Galeria\Domain\Entities\GalleryImageEntity;
use App\Modules\ContentManagement\Modules\Galeria\Domain\Service\GalleryService;
use App\Modules\ContentManagement\Modules\Galeria\Domain\ValueObjects\GalleryImageUrl;
use App\Modules\ContentManagement\Modules\Galeria\Domain\ValueObjects\ImageDescription;
use App\Modules\ContentManagement\StorageProviderInterface;

final readonly class SaveGalleryImageUseCase
{
    public function __construct(
        private GalleryService $service,
        private AuthorizationServiceInterface $authorizationService,
        private StorageProviderInterface $storageProvider,
    ) {}

    public function execute(SaveGalleryImageDTO $dto): void
    {
        $this->authorizationService->assertCan('manage.gallery');

        $imageUrl = $this->storageProvider->saveImage($dto->image);

        $galleryImage = GalleryImageEntity::create(
            url: new GalleryImageUrl($imageUrl),
            description: new ImageDescription($dto->description),
        );

        $this->service->save($galleryImage);
    }
}