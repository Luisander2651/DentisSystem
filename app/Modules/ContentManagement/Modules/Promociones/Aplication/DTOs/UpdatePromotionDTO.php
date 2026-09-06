<?php

declare(strict_types=1);

namespace App\Modules\ContentManagement\Modules\Promociones\Aplication\DTOs;

use App\Modules\ContentManagement\Modules\Promociones\Aplication\Exceptions\PromotionInputException;

final readonly class UpdatePromotionDTO
{
    public function __construct(
        public string $id,
        public ?string $name = null,
        public ?string $description = null,
        public ?float $discountPercentage = null,
        public ?string $startDate = null,
        public ?string $endDate = null,
        public ?string $status = null,
    ) {
        if (empty($id)) {
            throw PromotionInputException::missingRequiredFields();
        }

        if ($name === null && $description === null && $discountPercentage === null && $startDate === null && $endDate === null && $status === null) {
            throw PromotionInputException::atLeastOneFieldRequired();
        }
    }
}