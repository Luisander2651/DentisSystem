<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Aplication\DTOs;

final readonly class GetAppointmentByIdDTO
{
    private function __construct(
        public string $appointmentId,
    ) {}

    public static function create(
        string $appointmentId,
    ): self
    {
        return new self(
            appointmentId: $appointmentId,
        );
    }
}