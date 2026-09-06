<?php

declare(strict_types=1);

namespace App\Modules\ContentManagement\Modules\Certificaciones\Aplication\UseCases;

use App\Modules\ContentManagement\Modules\Certificaciones\Aplication\DTOs\DeleteCertificationDTO;
use App\Modules\ContentManagement\Modules\Certificaciones\Domain\ValueObjects\CertificationId;
use App\Modules\ContentManagement\Modules\Certificaciones\Domain\Service\CertificationsService;
use App\Core\Authorization\AuthorizationServiceInterface;
use App\Modules\ContentManagement\StorageProviderInterface;
use App\Modules\ContentManagement\Modules\Certificaciones\Aplication\Exceptions\CertificationException;


final readonly class DeleteCertificationUseCase
{
    public function __construct(
        private CertificationsService $service,
        private AuthorizationServiceInterface $authorizationService,
        private StorageProviderInterface $storageProvider,
    ) {}

    public function execute(DeleteCertificationDTO $dto): void
    {
        $this->authorizationService->assertCan('manage.certifications');

        $idVo = CertificationId::fromPrimitive($dto->id);
        $found = $this->service->getAllByNameOrId($idVo, null);

        if (empty($found)) {
            throw CertificationException::notFound($idVo);
        }

        $this->storageProvider->deleteImage($found[0]->imageUrl()->value);
        $this->service->deleteById($idVo);
    }
}
