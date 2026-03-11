<?php

declare(strict_types=1);

namespace App\Modules\Patients\Domain\Exceptions\ValueObjects\ContactInfo;

use App\Modules\Patients\Domain\Exceptions\ValueObjectsException;

final class ContactEmailException extends ValueObjectsException
{
    public static function invalidFormat(string $value): self
    {
        return new self("Invalid contact email format: {$value}.");
    }
}

