<?php

declare(strict_types=1);

namespace App\Modules\Patients\Domain\ValueObjects\MedicalData;

use App\Modules\Patients\Domain\Exceptions\ValueObjects\MedicalData\MedicalDataPatientIdException;

final readonly class MedicalDataPatientId
{
    private function __construct(
        public int $value,
    ) {}

    public static function fromInt(int $patientId): self
    {
        if ($patientId <= 0) {
            throw MedicalDataPatientIdException::mustBeGreaterThanZero($patientId);
        }

        return new self($patientId);
    }
}

