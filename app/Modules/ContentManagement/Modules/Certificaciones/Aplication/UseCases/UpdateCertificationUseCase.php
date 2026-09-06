<?php

declare(strict_types=1);

namespace App\Modules\ContentManagement\Modules\Certificaciones\Aplication\UseCases;

use App\Modules\ContentManagement\Modules\Certificaciones\Domain\Service\CertificationsService;
use App\Modules\ContentManagement\Modules\Certificaciones\Aplication\DTOs\UpdateCertificationDTO;
use App\Modules\ContentManagement\Modules\Certificaciones\Domain\ValueObjects\CertificationId;
use App\Modules\ContentManagement\Modules\Certificaciones\Domain\ValueObjects\ImageUrl;
use App\Modules\ContentManagement\StorageProviderInterface;
use App\Core\Authorization\AuthorizationServiceInterface;
use App\Modules\ContentManagement\Modules\Certificaciones\Domain\Exceptions\CertificationException;

final readonly class UpdateCertificationUseCase
{
    public function __construct(
        private CertificationsService $service,
        private AuthorizationServiceInterface $authorizationService,
        private StorageProviderInterface $storageProvider,
    ) {}

    public function execute(UpdateCertificationDTO $dto): void
    {
        $this->authorizationService->assertCan('manage.certifications');

        $idVo = CertificationId::fromPrimitive($dto->id);

        $found = $this->service->getAllByNameOrId($idVo, null);

        if (empty($found)) {
            throw CertificationException::notFound($idVo);
        }

        $entity = $found[0];

        $imageUrl = null;
        if ($dto->image !== null) {
            $imageUrl = $this->storageProvider->updateImage($entity->ImageUrl()->value, $dto->image);
        }

        $entity->update(
            $dto->name,
            $dto->description,
            $imageUrl,
        );

        $this->service->save($entity);
    }
}
