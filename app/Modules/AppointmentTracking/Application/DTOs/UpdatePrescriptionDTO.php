<?php

declare(strict_types=1);

namespace App\Modules\AppointmentTracking\Application\DTOs;

final readonly class UpdatePrescriptionDTO
{
    public function __construct(
        public string $prescriptionId,
        public ?string $medication,
        public ?string $dosage,
        public ?int $durationDays,
        public ?int $dailyFrequency,
        public ?string $instructions,
    ) {}

    public static function create(
        string $prescriptionId,
        ?string $medication,
        ?string $dosage,
        ?int $durationDays,
        ?int $dailyFrequency,
        ?string $instructions,
    ): self {
        return new self(
            prescriptionId: $prescriptionId,
            medication: $medication,
            dosage: $dosage,
            durationDays: $durationDays,
            dailyFrequency: $dailyFrequency,
            instructions: $instructions,
        );
    }

    public function hasAtLeastOneField(): bool
    {
        return $this->medication !== null
            || $this->dosage !== null
            || $this->durationDays !== null
            || $this->dailyFrequency !== null
            || $this->instructions !== null;
    }
}
