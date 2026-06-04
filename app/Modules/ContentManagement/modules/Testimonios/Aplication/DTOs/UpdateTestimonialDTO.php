<?php

declare(strict_types=1);

namespace App\Modules\ContentManagement\Modules\Testimonios\Aplication\DTOs;

use App\Modules\ContentManagement\Modules\Testimonios\Aplication\Exceptions\TestimonialInputException;

final readonly class UpdateTestimonialDTO
{
    public function __construct(
        public string $id,
        public ?string $author = null,
        public ?string $description = null,
        public ?string $status = null,
    ) {
        if (empty($id)) {
            throw TestimonialInputException::missingRequiredFields();
        }

        if ($author === null && $description === null && $status === null) {
            throw TestimonialInputException::atLeastOneFieldRequired();
        }
    }
}