<?php

declare(strict_types=1);

namespace App\Modules\ContentManagement\Modules\Galeria\Aplication\UseCases;

use App\Core\Authorization\AuthorizationServiceInterface;
use App\Modules\ContentManagement\Modules\Galeria\Aplication\DTOs\UpdateGalleryImageDTO;
use App\Modules\ContentManagement\Modules\Galeria\Domain\Entities\GalleryImageEntity;
use App\Modules\ContentManagement\Modules\Galeria\Domain\Exceptions\GalleryImageException;
use App\Modules\ContentManagement\Modules\Galeria\Domain\Service\GalleryService;
use App\Modules\ContentManagement\Modules\Galeria\Domain\ValueObjects\GalleryImageId;
use App\Modules\ContentManagement\Modules\Galeria\Domain\ValueObjects\GalleryImageUrl;
use App\Modules\ContentManagement\StorageProviderInterface;

final readonly class UpdateGalleryImageUseCase
{
    public function __construct(
        private GalleryService $service,
        private AuthorizationServiceInterface $authorizationService,
        private StorageProviderInterface $storageProvider,
    ) {}

    public function execute(UpdateGalleryImageDTO $dto): void
    {
        $this->authorizationService->assertCan('manage.gallery');

        $idVo = GalleryImageId::fromInt((int) $dto->id);
        $found = $this->service->getAllByUrlOrId($idVo, null, null);

        if (empty($found)) {
            throw GalleryImageException::notFound($idVo);
        }

        $entity = $found[0];

        $newUrl = null;
        if ($dto->image !== null) {
            $newUrl = $this->storageProvider->updateImage($entity->Url()->value, $dto->image);
        }

        $entity->update($dto->description, $newUrl, $dto->status);

        $this->service->save($entity);
    }
}