<?php

declare(strict_types=1);

namespace App\Modules\Patients\Domain\Exceptions\ValueObjects\Addresses;

use App\Modules\Patients\Domain\Exceptions\ValueObjectsException;

final class AddressIdException extends ValueObjectsException
{
    public static function mustBeGreaterThanZero(int $value): self
    {
        return new self("Address id must be greater than zero. Received: {$value}.");
    }
}

