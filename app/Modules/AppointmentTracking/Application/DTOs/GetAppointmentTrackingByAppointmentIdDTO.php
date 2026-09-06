<?php

declare(strict_types=1);

namespace App\Modules\AppointmentTracking\Application\DTOs;

final readonly class GetAppointmentTrackingByAppointmentIdDTO
{
    private function __construct(
        public string $appointmentId,
    ) {}

    public static function create(string $appointmentId): self
    {
        return new self(appointmentId: $appointmentId);
    }
}
