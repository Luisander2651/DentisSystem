<?php

declare(strict_types=1);

namespace App\Modules\AppointmentTracking\Application\DTOs;

final readonly class UpdateAppointmentTrackingDTO
{
    public function __construct(
        public string $appointmentTrackingId,
        public ?string $reason,
        public ?array $symptoms,
        public ?string $diagnosis,
        public ?string $procedurePerformed,
        public ?string $observations,
        public ?string $recommendations,
    ) {}

    public static function create(
        string $appointmentTrackingId,
        ?string $reason,
        ?array $symptoms,
        ?string $diagnosis,
        ?string $procedurePerformed,
        ?string $observations,
        ?string $recommendations,
    ): self {
        return new self(
            appointmentTrackingId: $appointmentTrackingId,
            reason: $reason,
            symptoms: $symptoms,
            diagnosis: $diagnosis,
            procedurePerformed: $procedurePerformed,
            observations: $observations,
            recommendations: $recommendations,
        );
    }

    public function hasAtLeastOneField(): bool
    {
        return $this->reason !== null
            || $this->symptoms !== null
            || $this->diagnosis !== null
            || $this->procedurePerformed !== null
            || $this->observations !== null
            || $this->recommendations !== null;
    }
}
