<?php

declare(strict_types=1);

namespace App\Modules\Patients\Domain\Exceptions\ValueObjects\ContactInfo;

use App\Modules\Patients\Domain\Exceptions\ValueObjectsException;

final class ContactInfoIdException extends ValueObjectsException
{
    public static function mustBeGreaterThanZero(int $value): self
    {
        return new self("Contact info id must be greater than zero. Received: {$value}.");
    }
}

