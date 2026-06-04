<?php

declare(strict_types=1);

namespace App\Modules\ContentManagement\Modules\Testimonios\Aplication\Exceptions;

use RuntimeException;

final class TestimonialInputException extends RuntimeException
{
    public static function missingRequiredFields(): self
    {
        return new self('Missing required testimonial fields.');
    }

    public static function atLeastOneFieldRequired(): self
    {
        return new self('At least one testimonial field must be provided.');
    }
}