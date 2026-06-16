<?php

declare(strict_types=1);

namespace App\Modules\ContentManagement\Modules\Galeria\Aplication\UseCases;

use App\Core\Authorization\AuthorizationServiceInterface;
use App\Modules\ContentManagement\Modules\Galeria\Aplication\DTOs\DeleteGalleryImageDTO;
use App\Modules\ContentManagement\Modules\Galeria\Domain\Exceptions\GalleryImageException;
use App\Modules\ContentManagement\Modules\Galeria\Domain\Service\GalleryService;
use App\Modules\ContentManagement\Modules\Galeria\Domain\ValueObjects\GalleryImageId;
use App\Modules\ContentManagement\StorageProviderInterface;

final readonly class DeleteGalleryImageUseCase
{
    public function __construct(
        private GalleryService $service,
        private AuthorizationServiceInterface $authorizationService,
        private StorageProviderInterface $storageProvider,
    ) {}

    public function execute(DeleteGalleryImageDTO $dto): void
    {
        $this->authorizationService->assertCan('manage.gallery');

        $idVo = GalleryImageId::fromInt((int) $dto->id);
        $found = $this->service->getAllByUrlOrId($idVo, null, null);

        if (empty($found)) {
            throw GalleryImageException::notFound($idVo);
        }

        $this->storageProvider->deleteImage($found[0]->Url()->value);
        $this->service->deleteById($idVo);
    }
}