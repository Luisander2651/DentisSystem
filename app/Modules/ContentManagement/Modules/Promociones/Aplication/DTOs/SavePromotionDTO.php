<?php

declare(strict_types=1);

namespace App\Modules\ContentManagement\Modules\Promociones\Aplication\DTOs;

use App\Modules\ContentManagement\Modules\Promociones\Aplication\Exceptions\PromotionInputException;

final readonly class SavePromotionDTO
{
    public function __construct(
        public string $name,
        public string $description,
        public ?float $discountPercentage,
        public string $startDate,
        public string $endDate,
        public ?string $status = null,
    ) {
        if (empty($name) || empty($description) || empty($startDate) || empty($endDate) || $discountPercentage === null) {
            throw PromotionInputException::missingRequiredFields();
        }
    }
}