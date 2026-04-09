<?php

declare(strict_types=1);

namespace App\Modules\Patients\Aplication\DTOs\MedicalData;

final readonly class CreateMedicalDataDTO
{
    private function __construct(
        public string $patientId,
        public ?string $bloodType,
        public ?array $allergies,
        public ?array $medications,
        public ?array $lastDentistVisit,
    ) {}

    public static function create(
        string $patientId,
        ?string $bloodType = null,
        ?array $allergies = null,
        ?array $medications = null,
        ?array $lastDentistVisit = null,
    ): self {
        return new self(
            patientId: $patientId,
            bloodType: ($bloodType === '') ? null : $bloodType,
            allergies: $allergies,
            medications: $medications,
            lastDentistVisit: $lastDentistVisit,
        );
    }
}
