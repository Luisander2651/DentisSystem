<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Aplication\DTOs;

final readonly class CreateAppointmentDTO
{
    public function __construct(
        public string $date,
        public string $time,
        public string $treatmentId,
        public string $userId,
        public string $patientId,
    ) {}

    public static function create(
        string $date,
        string $time,
        string $treatmentId,
        string $userId,
        string $patientId,
    ): self
    {
        return new self(
            date: $date,
            time: $time,
            treatmentId: $treatmentId,
            userId: $userId,
            patientId: $patientId,
        );
    }
}   