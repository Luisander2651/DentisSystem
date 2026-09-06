<?php

declare(strict_types=1);

namespace App\Modules\ContentManagement\Modules\Testimonios\Aplication\DTOs;

use App\Modules\ContentManagement\Modules\Testimonios\Aplication\Exceptions\TestimonialInputException;

final readonly class SaveTestimonialDTO
{
    public function __construct(
        public string $author,
        public string $description,
        public ?string $status = null,
    ) {
        if (empty($author) || empty($description)) {
            throw TestimonialInputException::missingRequiredFields();
        }
    }
}