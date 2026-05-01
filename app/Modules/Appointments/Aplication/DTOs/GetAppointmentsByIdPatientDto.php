<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Aplication\DTOs;

final readonly class GetAppointmentsByIdPatientDto
{
    private function __construct(
        public string $patientId,
    ) {}

    public static function create(
        string $patientId,
    ): self
    {
        return new self(
            patientId: $patientId,
        );
    }
}