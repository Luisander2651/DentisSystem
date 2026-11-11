<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Aplication\DTOs;

/**
 * Spec 025 (CA1, D6): asking for the next appointment of the portal carries only the date of
 * today in the clinic's time zone (Y-m-d); the patient is the one of the session.
 */
final readonly class GetPatientNextAppointmentDTO
{
    private function __construct(
        public string $today,
    ) {}

    public static function create(string $today): self
    {
        return new self(
            today: $today,
        );
    }
}
