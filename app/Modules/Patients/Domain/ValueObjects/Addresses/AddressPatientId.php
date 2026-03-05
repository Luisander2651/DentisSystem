<?php

declare(strict_types=1);

namespace App\Modules\Patients\Domain\ValueObjects\Addresses;

final readonly class AddressPatientId
{
    private function __construct(
        public int $value,
    ) {}

    public static function fromInt(int $patientId): self
    {
        if ($patientId <= 0) {
            throw new \InvalidArgumentException('Patient id must be greater than zero.');
        }

        return new self($patientId);
    }
}
