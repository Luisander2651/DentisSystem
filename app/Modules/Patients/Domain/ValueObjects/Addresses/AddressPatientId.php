<?php

declare(strict_types=1);

namespace App\Modules\Patients\Domain\ValueObjects\Addresses;

use App\Modules\Patients\Domain\Exceptions\ValueObjects\Addresses\AddressPatientIdException;

final readonly class AddressPatientId
{
    private function __construct(
        public int $value,
    ) {}

    public static function fromInt(int $patientId): self
    {
        if ($patientId <= 0) {
            throw AddressPatientIdException::mustBeGreaterThanZero($patientId);
        }

        return new self($patientId);
    }
}

