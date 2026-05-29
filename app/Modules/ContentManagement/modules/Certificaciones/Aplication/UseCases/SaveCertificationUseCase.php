<?php

declare(strict_types=1);

namespace App\Modules\ContentManagement\Modules\Certificaciones\Aplication\UseCases;

use App\Modules\ContentManagement\Modules\Certificaciones\Domain\Service\CertificationsService;
use App\Modules\ContentManagement\Modules\Certificaciones\Aplication\DTOs\SaveCertificationDTO;
use App\Modules\ContentManagement\Modules\Certificaciones\Domain\Entities\CertificationEntity;
use App\Modules\ContentManagement\Modules\Certificaciones\Domain\ValueObjects\CertificationName;
use App\Modules\ContentManagement\Modules\Certificaciones\Domain\ValueObjects\CertificationDescription;
use App\Modules\ContentManagement\Modules\Certificaciones\Domain\ValueObjects\CertificationDate;
use App\Modules\ContentManagement\Modules\Certificaciones\Domain\ValueObjects\ImageUrl;
use App\Modules\ContentManagement\StorageProviderInterface;
use App\Core\Authorization\AuthorizationServiceInterface;

final readonly class SaveCertificationUseCase
{
    public function __construct(
        private CertificationsService $service,
        private AuthorizationServiceInterface $authorizationService,
        private StorageProviderInterface $storageProvider,
    ) {
    }

    public function execute(SaveCertificationDTO $dto): void
    {
        $this->authorizationService->assertCan('manage.certifications');

        $imageUrl = $this->storageProvider->saveImage($dto->image);

        $certification = CertificationEntity::create(
            name: new CertificationName($dto->name),
            description: new CertificationDescription($dto->description),
            date: new CertificationDate($dto->date),
            imageUrl: new ImageUrl($imageUrl),
        );

        $this->service->save($certification);
    }
    
}