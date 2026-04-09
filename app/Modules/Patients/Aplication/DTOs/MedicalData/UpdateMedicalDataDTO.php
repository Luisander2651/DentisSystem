<?php

declare(strict_types=1);

namespace App\Modules\Patients\Aplication\DTOs\MedicalData;

final readonly class UpdateMedicalDataDTO
{
    private function __construct(
        public ?string $bloodType,
        public ?array $allergies,
        public ?array $medications,
        public ?array $lastDentistVisit,
    ) {}

    public static function create(
        ?string $bloodType = null,
        ?array $allergies = null,
        ?array $medications = null,
        ?array $lastDentistVisit = null,
    ): self {
        return new self(
            bloodType: ($bloodType === '') ? null : $bloodType,
            allergies: $allergies,
            medications: $medications,
            lastDentistVisit: $lastDentistVisit,
        );
    }

    public function hasValue(): bool
    {
        return $this->bloodType !== null ||
            $this->allergies !== null ||
            $this->medications !== null ||
            $this->lastDentistVisit !== null;
    }
}
