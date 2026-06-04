<?php

declare(strict_types=1);

namespace App\Modules\ContentManagement\Modules\Galeria\Aplication\Exceptions;

use RuntimeException;

final class GalleryImageInputException extends RuntimeException
{
    public static function missingRequiredFields(): self
    {
        return new self('Missing required gallery image fields.');
    }

    public static function atLeastOneFieldRequired(): self
    {
        return new self('At least one gallery image field must be provided.');
    }
}