<?php

declare(strict_types=1);

namespace App\Modules\ContentManagement\Modules\Testimonios\Aplication\DTOs;

use App\Modules\ContentManagement\Modules\Testimonios\Aplication\Exceptions\TestimonialInputException;

final readonly class DeleteTestimonialDTO
{
    public function __construct(
        public string $id,
    ) {
        if (empty($id)) {
            throw TestimonialInputException::missingRequiredFields();
        }
    }
}