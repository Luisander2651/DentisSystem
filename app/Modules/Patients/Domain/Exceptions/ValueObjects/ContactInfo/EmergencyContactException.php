<?php

declare(strict_types=1);

namespace App\Modules\Patients\Domain\Exceptions\ValueObjects\ContactInfo;

use App\Modules\Patients\Domain\Exceptions\ValueObjectsException;

final class EmergencyContactException extends ValueObjectsException
{
    public static function tooLong(int $maxLength): self
    {
        return new self("Emergency contact is too long. Maximum allowed length is {$maxLength} characters.");
    }
}

