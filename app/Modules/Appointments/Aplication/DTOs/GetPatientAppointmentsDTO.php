<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Aplication\DTOs;

/**
 * Spec 025 (CA13, A20): asking for the appointments of the portal carries nothing about the
 * patient; the patient is the one of the session.
 */
final readonly class GetPatientAppointmentsDTO
{
    private function __construct() {}

    public static function create(): self
    {
        return new self;
    }
}
