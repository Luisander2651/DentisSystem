<?php

declare(strict_types=1);

namespace App\Modules\AppointmentTracking\Application\Support;

use App\Modules\AppointmentTracking\Domain\Entities\AppointmentTrackingEntity;
use App\Modules\AppointmentTracking\Domain\Entities\PrescriptionEntity;
use App\Modules\AppointmentTracking\Domain\ValueObjects\AppointmentTrackingAppointmentId;
use App\Modules\AppointmentTracking\Domain\ValueObjects\DailyFrequency;
use App\Modules\AppointmentTracking\Domain\ValueObjects\Diagnosis;
use App\Modules\AppointmentTracking\Domain\ValueObjects\Dosage;
use App\Modules\AppointmentTracking\Domain\ValueObjects\DurationDays;
use App\Modules\AppointmentTracking\Domain\ValueObjects\Instructions;
use App\Modules\AppointmentTracking\Domain\ValueObjects\Medication;
use App\Modules\AppointmentTracking\Domain\ValueObjects\Observations;
use App\Modules\AppointmentTracking\Domain\ValueObjects\PrescriptionAppointmentTrackingId;
use App\Modules\AppointmentTracking\Domain\ValueObjects\ProcedurePerformed;
use App\Modules\AppointmentTracking\Domain\ValueObjects\Reason;
use App\Modules\AppointmentTracking\Domain\ValueObjects\Recommendations;
use App\Modules\AppointmentTracking\Domain\ValueObjects\Symptoms;

/**
 * Spec 020 (D12): the clinical follow-up and its prescriptions, built with the same rules when an
 * appointment is completed and when the follow-up of a completed one is registered.
 */
final readonly class TrackingFactory
{
    /**
     * @param  list<string>  $symptoms
     */
    public static function tracking(
        string $appointmentId,
        string $reason,
        array $symptoms,
        string $diagnosis,
        string $procedurePerformed,
        ?string $observations,
        ?string $recommendations,
    ): AppointmentTrackingEntity {
        return AppointmentTrackingEntity::create(
            appointmentId: new AppointmentTrackingAppointmentId($appointmentId),
            reason: Reason::fromString($reason),
            symptoms: Symptoms::fromArray($symptoms),
            diagnosis: Diagnosis::fromString($diagnosis),
            procedurePerformed: ProcedurePerformed::fromString($procedurePerformed),
            observations: Observations::fromNullable($observations),
            recommendations: Recommendations::fromNullable($recommendations),
        );
    }

    /**
     * El id ya existe en memoria (generado por AppointmentTrackingId::random() dentro de create()),
     * así que cada receta se construye referenciándolo directo — nunca se confía en un
     * appointment_tracking_id que venga suelto del request.
     *
     * @param  list<array<string, mixed>>  $prescriptions
     * @return list<PrescriptionEntity>
     */
    public static function prescriptions(AppointmentTrackingEntity $tracking, array $prescriptions): array
    {
        $trackingId = $tracking->Id()->value;

        return array_map(
            fn (array $p) => PrescriptionEntity::create(
                appointmentTrackingId: new PrescriptionAppointmentTrackingId($trackingId),
                medication: Medication::fromString((string) ($p['medication'] ?? '')),
                dosage: Dosage::fromString((string) ($p['dosage'] ?? '')),
                durationDays: DurationDays::fromInt((int) $p['durationDays']),
                dailyFrequency: DailyFrequency::fromInt((int) $p['dailyFrequency']),
                instructions: Instructions::fromNullable($p['instructions'] ?? null),
            ),
            $prescriptions,
        );
    }
}
