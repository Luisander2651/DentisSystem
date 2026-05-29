<?php

declare(strict_types=1);

namespace App\Modules\ContentManagement\Modules\Certificaciones\Aplication\UseCases;

use App\Modules\ContentManagement\Modules\Certificaciones\Aplication\DTOs\DeleteCertificationDTO;
use App\Modules\ContentManagement\Modules\Certificaciones\Domain\ValueObjects\CertificationId;
use App\Modules\ContentManagement\Modules\Certificaciones\Domain\Service\CertificationsService;
use App\Core\Authorization\AuthorizationServiceInterface;

final readonly class DeleteCertificationUseCase
{
    public function __construct(
        private CertificationsService $service,
        private AuthorizationServiceInterface $authorizationService,
    ) {}

    public function execute(DeleteCertificationDTO $dto): void
    {
        $this->authorizationService->assertCan('manage.certifications');

        $idVo = CertificationId::fromPrimitive($dto->id);

        $this->service->deleteById($idVo);
    }
}
