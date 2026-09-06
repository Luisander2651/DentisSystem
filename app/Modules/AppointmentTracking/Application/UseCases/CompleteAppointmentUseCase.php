<?php

declare(strict_types=1);

namespace App\Modules\AppointmentTracking\Application\UseCases;

use App\Core\Authorization\AuthorizationServiceInterface;
use App\Modules\Appointments\Domain\Exceptions\AppointmentException;
use App\Modules\Appointments\Domain\Service\AppointmentsService;
use App\Modules\Appointments\Domain\ValueObjects\AppointmentId;
use App\Modules\AppointmentTracking\Application\DTOs\CompleteAppointmentDTO;
use App\Modules\AppointmentTracking\Domain\Entities\AppointmentTrackingEntity;
use App\Modules\AppointmentTracking\Domain\Entities\AppointmentTrackingRecord;
use App\Modules\AppointmentTracking\Domain\Entities\PrescriptionEntity;
use App\Modules\AppointmentTracking\Domain\Exceptions\AppointmentTrackingException;
use App\Modules\AppointmentTracking\Domain\Service\AppointmentCompletionService;
use App\Modules\AppointmentTracking\Domain\Service\AppointmentTrackingService;
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

final readonly class CompleteAppointmentUseCase
{
    public function __construct(
        private AuthorizationServiceInterface $authorization,
        private AppointmentsService $appointmentsService,
        private AppointmentTrackingService $appointmentTrackingService,
        private AppointmentCompletionService $appointmentCompletionService,
    ) {}

    public function execute(CompleteAppointmentDTO $dto): AppointmentTrackingRecord
    {
        $this->authorization->assertCan('appointment-tracking.create');

        $appointmentId = new AppointmentId($dto->appointmentId);
        $appointment = $this->appointmentsService->findById($appointmentId);

        if (! $appointment) {
            throw AppointmentException::notFound($appointmentId);
        }

        if ($appointment->Status()->isCancelled()) {
            throw AppointmentException::invalidStatusTransition();
        }

        $trackingAppointmentId = new AppointmentTrackingAppointmentId($dto->appointmentId);
        $existingTracking = $this->appointmentTrackingService->findByAppointmentId($trackingAppointmentId);

        if ($existingTracking !== null) {
            throw AppointmentTrackingException::alreadyExistsForAppointment($dto->appointmentId);
        }

        $appointment->complete();

        $tracking = AppointmentTrackingEntity::create(
            appointmentId: $trackingAppointmentId,
            reason: Reason::fromString($dto->reason),
            symptoms: Symptoms::fromArray($dto->symptoms),
            diagnosis: Diagnosis::fromString($dto->diagnosis),
            procedurePerformed: ProcedurePerformed::fromString($dto->procedurePerformed),
            observations: Observations::fromNullable($dto->observations),
            recommendations: Recommendations::fromNullable($dto->recommendations),
        );

        // El id ya existe en memoria (generado por AppointmentTrackingId::random() dentro de create()),
        // así que cada receta se construye referenciándolo directo — nunca se confía en un
        // appointment_tracking_id que venga suelto del request.
        $trackingId = $tracking->Id()->value;

        $prescriptions = array_map(
            fn (array $p) => PrescriptionEntity::create(
                appointmentTrackingId: new PrescriptionAppointmentTrackingId($trackingId),
                medication: Medication::fromString((string) ($p['medication'] ?? '')),
                dosage: Dosage::fromString((string) ($p['dosage'] ?? '')),
                durationDays: DurationDays::fromInt((int) $p['durationDays']),
                dailyFrequency: DailyFrequency::fromInt((int) $p['dailyFrequency']),
                instructions: Instructions::fromNullable($p['instructions'] ?? null),
            ),
            $dto->prescriptions,
        );

        return $this->appointmentCompletionService->complete($appointment, $tracking, $prescriptions);
    }
}
