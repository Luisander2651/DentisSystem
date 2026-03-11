<?php

declare(strict_types=1);

namespace App\Modules\Patients\Domain\Exceptions\ValueObjects\ContactInfo;

use App\Modules\Patients\Domain\Exceptions\ValueObjectsException;

final class ContactInfoPatientIdException extends ValueObjectsException
{
    public static function mustBeGreaterThanZero(int $value): self
    {
        return new self("Patient id must be greater than zero. Received: {$value}.");
    }
}

