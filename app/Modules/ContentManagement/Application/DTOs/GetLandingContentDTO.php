<?php

declare(strict_types=1);

namespace App\Modules\ContentManagement\Application\DTOs;

final readonly class GetLandingContentDTO
{
    public function __construct(
        public ?string $status = null,
    ) {}
}
