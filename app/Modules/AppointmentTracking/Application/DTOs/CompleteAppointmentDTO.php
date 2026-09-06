<?php

declare(strict_types=1);

namespace App\Modules\AppointmentTracking\Application\DTOs;

use App\Modules\AppointmentTracking\Application\Exceptions\AppointmentTrackingApplicationException;

final readonly class CompleteAppointmentDTO
{
    private const ALLOWED_PRESCRIPTION_FIELDS = ['medication', 'dosage', 'durationDays', 'dailyFrequency', 'instructions'];

    private const REQUIRED_PRESCRIPTION_FIELDS = ['medication', 'dosage', 'durationDays', 'dailyFrequency'];

    public function __construct(
        public string $appointmentId,
        public string $reason,
        public array $symptoms,
        public string $diagnosis,
        public string $procedurePerformed,
        public ?string $observations,
        public ?string $recommendations,
        public array $prescriptions,
    ) {}

    public static function create(
        string $appointmentId,
        string $reason,
        array $symptoms,
        string $diagnosis,
        string $procedurePerformed,
        ?string $observations,
        ?string $recommendations,
        array $prescriptions,
    ): self {
        self::assertValidPrescriptions($prescriptions);

        return new self(
            appointmentId: $appointmentId,
            reason: $reason,
            symptoms: $symptoms,
            diagnosis: $diagnosis,
            procedurePerformed: $procedurePerformed,
            observations: $observations,
            recommendations: $recommendations,
            prescriptions: $prescriptions,
        );
    }

    private static function assertValidPrescriptions(array $prescriptions): void
    {
        foreach ($prescriptions as $index => $prescription) {
            if (! is_array($prescription)) {
                throw AppointmentTrackingApplicationException::invalidPrescriptionFormat((int) $index);
            }

            $unexpectedFields = array_diff(array_keys($prescription), self::ALLOWED_PRESCRIPTION_FIELDS);
            if (! empty($unexpectedFields)) {
                throw AppointmentTrackingApplicationException::unexpectedPrescriptionFields((int) $index, array_values($unexpectedFields));
            }

            $missingFields = array_diff(self::REQUIRED_PRESCRIPTION_FIELDS, array_keys($prescription));
            if (! empty($missingFields)) {
                throw AppointmentTrackingApplicationException::missingPrescriptionFields((int) $index, array_values($missingFields));
            }
        }
    }
}
