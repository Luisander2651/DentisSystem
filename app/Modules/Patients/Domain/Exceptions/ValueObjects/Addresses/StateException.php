<?php

declare(strict_types=1);

namespace App\Modules\Patients\Domain\Exceptions\ValueObjects\Addresses;

use App\Modules\Patients\Domain\Exceptions\ValueObjectsException;

final class StateException extends ValueObjectsException
{
    public static function tooLong(int $maxLength): self
    {
        return new self("State is too long. Maximum allowed length is {$maxLength} characters.");
    }
}

