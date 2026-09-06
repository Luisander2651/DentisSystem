<?php

declare(strict_types=1);

namespace App\Modules\ContentManagement\Modules\Promociones\Aplication\DTOs;

use App\Modules\ContentManagement\Modules\Promociones\Aplication\Exceptions\PromotionInputException;

final readonly class DeletePromotionDTO
{
    public function __construct(
        public string $id,
    ) {
        if (empty($id)) {
            throw PromotionInputException::missingRequiredFields();
        }
    }
}