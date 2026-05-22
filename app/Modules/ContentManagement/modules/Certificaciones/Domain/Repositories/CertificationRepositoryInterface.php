<?php

namespace App\Modules\ContentManagement\Modules\Certificaciones\Domain\Repositories;

use App\Modules\ContentManagement\Modules\Certificaciones\Domain\Entities\CertificationEntity;
use App\Modules\ContentManagement\Modules\Certificaciones\Domain\ValueObjects\CertificationId;
use App\Modules\ContentManagement\Modules\Certificaciones\Domain\ValueObjects\CertificationName;
interface CertificationRepositoryInterface
{
    public function save(CertificationEntity $data): void;


    public function destroy(CertificationId $id): void;

    public function findByIdAndName(?CertificationId $id, ?CertificationName $name): array;

}
