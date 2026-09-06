<?php

declare(strict_types=1);

namespace App\Modules\AppointmentTracking\Application\DTOs;

final readonly class DeletePrescriptionDTO
{
    public function __construct(
        public string $prescriptionId,
    ) {}

    public static function create(string $prescriptionId): self
    {
        return new self(prescriptionId: $prescriptionId);
    }
}
