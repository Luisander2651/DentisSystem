<?php

namespace App\Modules\ContentManagement\Modules\Certificaciones\Domain\Repositories;

use App\Core\Domain\Pagination\Page;
use App\Core\Domain\Pagination\PageRequest;
use App\Modules\ContentManagement\Modules\Certificaciones\Domain\Entities\CertificationEntity;
use App\Modules\ContentManagement\Modules\Certificaciones\Domain\ValueObjects\CertificationId;
use App\Modules\ContentManagement\Modules\Certificaciones\Domain\ValueObjects\CertificationName;
use App\Modules\ContentManagement\Modules\Certificaciones\Domain\ValueObjects\CertificationStatus;

interface CertificationRepositoryInterface
{
    public function save(CertificationEntity $data): void;

    public function destroy(CertificationId $id): void;

    public function findByIdAndNameAndStatus(?CertificationId $id, ?CertificationName $name, ?CertificationStatus $status): array;

    /**
     * Spec 028 (CA7): one page of the list of the panel, newest first.
     *
     * @return Page<CertificationEntity>
     */
    public function findPage(PageRequest $request): Page;

    /**
     * Spec 028 (CA5): how many items the whole list holds, by status.
     *
     * @return array{total: int, visible: int, hidden: int}
     */
    public function countByStatus(): array;
}
