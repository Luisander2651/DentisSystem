<?php

declare(strict_types=1);

namespace App\Modules\Patients\Domain\ValueObjects\Addresses;

use App\Modules\Patients\Domain\Exceptions\ValueObjects\Addresses\AddressIdException;

final readonly class AddressId
{
    private function __construct(
        public int $value,
    ) {}

    public static function fromInt(int $id): self
    {
        if ($id <= 0) {
            throw AddressIdException::mustBeGreaterThanZero($id);
        }

        return new self($id);
    }
}

