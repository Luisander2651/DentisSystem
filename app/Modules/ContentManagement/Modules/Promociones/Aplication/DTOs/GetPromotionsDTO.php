<?php

declare(strict_types=1);

namespace App\Modules\ContentManagement\Modules\Promociones\Aplication\DTOs;

final readonly class GetPromotionsDTO
{
    public function __construct(
        public ?string $id = null,
        public ?string $name = null,
        public ?string $status = null,
    ) {}
}