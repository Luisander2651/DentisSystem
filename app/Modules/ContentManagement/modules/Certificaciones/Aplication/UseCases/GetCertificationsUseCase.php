<?php

declare(strict_types=1);

namespace App\Modules\ContentManagement\Modules\Certificaciones\Aplication\UseCases;

use App\Modules\ContentManagement\Modules\Certificaciones\Aplication\DTOs\GetCertificationsDTO;
use App\Modules\ContentManagement\Modules\Certificaciones\Domain\Service\CertificationsService;
use App\Modules\ContentManagement\Modules\Certificaciones\Domain\ValueObjects\CertificationId;
use App\Modules\ContentManagement\Modules\Certificaciones\Domain\ValueObjects\CertificationName;
use App\Core\Authorization\AuthorizationServiceInterface;

final readonly class GetCertificationsUseCase
{
    public function __construct(
        private CertificationsService $service,
        private AuthorizationServiceInterface $authorizationService,
    ) {}

    /**
     * @return array<int, \App\Modules\ContentManagement\Modules\Certificaciones\Domain\Entities\CertificationEntity>
     */
    public function execute(GetCertificationsDTO $dto): array
    {
        $this->authorizationService->assertCan('manage.certifications');

        $idVo = $dto->id ? CertificationId::fromPrimitive($dto->id) : null;
        $nameVo = $dto->name ? new CertificationName($dto->name) : null;

        return $this->service->getAllByNameOrId($idVo, $nameVo);
    }
}
