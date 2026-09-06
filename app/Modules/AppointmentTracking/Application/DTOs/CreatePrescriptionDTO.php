<?php

declare(strict_types=1);

namespace App\Modules\AppointmentTracking\Application\DTOs;

final readonly class CreatePrescriptionDTO
{
    public function __construct(
        public string $appointmentTrackingId,
        public string $medication,
        public string $dosage,
        public int $durationDays,
        public int $dailyFrequency,
        public ?string $instructions,
    ) {}

    public static function create(
        string $appointmentTrackingId,
        string $medication,
        string $dosage,
        int $durationDays,
        int $dailyFrequency,
        ?string $instructions,
    ): self {
        return new self(
            appointmentTrackingId: $appointmentTrackingId,
            medication: $medication,
            dosage: $dosage,
            durationDays: $durationDays,
            dailyFrequency: $dailyFrequency,
            instructions: $instructions,
        );
    }
}
