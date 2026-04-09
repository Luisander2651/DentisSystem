<?php

declare(strict_types=1);

namespace App\Modules\Patients\Aplication\DTOs\MedicalData;

final readonly class DeleteMedicalDataDTO
{
    private function __construct(
        public string $patientId,
    ) {}

    public static function create(string $patientId): self
    {
        return new self(patientId: $patientId);
    }
}
