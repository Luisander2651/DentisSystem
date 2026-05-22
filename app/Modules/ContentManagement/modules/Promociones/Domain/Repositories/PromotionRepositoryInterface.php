<?php

namespace App\Modules\ContentManagement\Modules\Promociones\Domain\Repositories;

use App\Modules\ContentManagement\Modules\Promociones\Domain\Entities\PromotionEntity;
use App\Modules\ContentManagement\Modules\Promociones\Domain\ValueObjects\PromotionId;
use App\Modules\ContentManagement\Modules\Promociones\Domain\ValueObjects\PromotionName;
use App\Modules\ContentManagement\Modules\Promociones\Domain\ValueObjects\PromotionStatus;


interface PromotionRepositoryInterface
{
    public function save(PromotionEntity $data): void;

    public function destroy(PromotionId $id): void;

    public function findByIdAndName(?PromotionId $id, ?PromotionName $name, ?PromotionStatus $status): array;
}