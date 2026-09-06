<?php

declare(strict_types=1);

namespace App\Modules\ContentManagement\Modules\Testimonios\Aplication\DTOs;

final readonly class GetTestimonialsDTO
{
    public function __construct(
        public ?string $id = null,
        public ?string $status = null,
    ) {}
}