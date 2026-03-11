<?php

declare(strict_types=1);

namespace App\Modules\Patients\Domain\Exceptions\ValueObjects\Addresses;

use App\Modules\Patients\Domain\Exceptions\ValueObjectsException;

final class PostalCodeException extends ValueObjectsException
{
    public static function invalidFormat(string $value): self
    {
        return new self("Invalid postal code format: {$value}.");
    }
}

