<?php

declare(strict_types=1);

namespace App\Modules\Patients\Domain\ValueObjects\ContactInfo;

use App\Modules\Patients\Domain\Exceptions\ValueObjects\ContactInfo\ContactInfoPatientIdException;

final readonly class ContactInfoPatientId
{
    private function __construct(
        public int $value,
    ) {}

    public static function fromInt(int $patientId): self
    {
        if ($patientId <= 0) {
            throw ContactInfoPatientIdException::mustBeGreaterThanZero($patientId);
        }

        return new self($patientId);
    }
}

