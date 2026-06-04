<?php

declare(strict_types=1);

namespace App\Modules\ContentManagement\Modules\Promociones\Aplication\Exceptions;

use RuntimeException;

final class PromotionInputException extends RuntimeException
{
    public static function missingRequiredFields(): self
    {
        return new self('Missing required promotion fields.');
    }

    public static function atLeastOneFieldRequired(): self
    {
        return new self('At least one promotion field must be provided.');
    }
}