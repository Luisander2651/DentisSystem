<?php

declare(strict_types=1);

namespace App\Modules\ContentManagement\Modules\Galeria\Aplication\UseCases;

use App\Core\Authorization\AuthorizationServiceInterface;
use App\Modules\ContentManagement\Modules\Galeria\Aplication\DTOs\GetGalleryImagesDTO;
use App\Modules\ContentManagement\Modules\Galeria\Domain\Service\GalleryService;
use App\Modules\ContentManagement\Modules\Galeria\Domain\ValueObjects\GalleryImageId;
use App\Modules\ContentManagement\Modules\Galeria\Domain\ValueObjects\GalleryImageUrl;
use App\Modules\ContentManagement\Modules\Galeria\Domain\ValueObjects\GalleryStatus;

final readonly class GetGalleryImagesUseCase
{
    public function __construct(
        private GalleryService $service,
        private AuthorizationServiceInterface $authorizationService,
    ) {}

    public function execute(GetGalleryImagesDTO $dto): array
    {
        $this->authorizationService->assertCan('manage.gallery');

        $idVo = $dto->id ? GalleryImageId::fromInt((int) $dto->id) : null;
        $urlVo = $dto->url ? new GalleryImageUrl($dto->url) : null;
        $statusVo = $dto->status ? GalleryStatus::fromString($dto->status) : null;

        return $this->service->getAllByUrlOrId($idVo, $urlVo, $statusVo);
    }
}