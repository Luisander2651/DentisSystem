<?php

declare(strict_types=1);

namespace App\Modules\ContentManagement\Modules\Testimonios\Aplication\DTOs;

/**
 * Spec 027: what a visitor sends from /opinion. Only the name and the opinion: the status and the
 * date are never taken from the request (CA8).
 */
final readonly class SubmitPublicTestimonialDTO
{
    public function __construct(
        public string $author,
        public string $description,
    ) {}
}
