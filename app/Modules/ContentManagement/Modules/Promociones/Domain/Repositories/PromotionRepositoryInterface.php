<?php

namespace App\Modules\ContentManagement\Modules\Promociones\Domain\Repositories;

use App\Core\Domain\Pagination\Page;
use App\Core\Domain\Pagination\PageRequest;
use App\Modules\ContentManagement\Modules\Promociones\Domain\Entities\PromotionEntity;
use App\Modules\ContentManagement\Modules\Promociones\Domain\ValueObjects\PromotionId;
use App\Modules\ContentManagement\Modules\Promociones\Domain\ValueObjects\PromotionName;
use App\Modules\ContentManagement\Modules\Promociones\Domain\ValueObjects\PromotionStatus;

interface PromotionRepositoryInterface
{
    public function save(PromotionEntity $data): void;

    public function destroy(PromotionId $id): void;

    public function findByIdAndName(?PromotionId $id, ?PromotionName $name, ?PromotionStatus $status): array;

    /**
     * Spec 028 (CA7): one page of the list of the panel, newest first.
     *
     * @return Page<PromotionEntity>
     */
    public function findPage(PageRequest $request): Page;

    /**
     * Spec 028 (CA5): how many items the whole list holds, by status.
     *
     * @return array{total: int, visible: int, hidden: int}
     */
    public function countByStatus(): array;
}
