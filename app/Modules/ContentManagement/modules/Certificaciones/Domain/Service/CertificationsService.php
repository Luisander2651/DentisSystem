<?php

declare(strict_types=1);

namespace App\Modules\ContentManagement\Modules\Certificaciones\Domain\Service;

use App\Modules\ContentManagement\Modules\Certificaciones\Domain\Entities\CertificationEntity;
use App\Modules\ContentManagement\Modules\Certificaciones\Domain\Repositories\CertificationRepositoryInterface;
use App\Modules\ContentManagement\Modules\Certificaciones\Domain\ValueObjects\CertificationId;
use App\Modules\ContentManagement\Modules\Certificaciones\Domain\ValueObjects\CertificationName;

final readonly class CertificationsService
{
    public function __construct(
        private CertificationRepositoryInterface $repository,
    ) {
    }

    public function save(CertificationEntity $certification): void
    {
        $this->repository->save($certification);
    }

    public function deleteById(CertificationId $id): void
    {
        $this->repository->destroy($id);
    }

    public function getAllByNameOrId(?CertificationId $id, ?CertificationName $name): array
    {
        return $this->repository->findByIdAndName(
            id: $id,
            name: $name,
        );
    }
}