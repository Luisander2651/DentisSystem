<?php

declare(strict_types=1);

namespace App\Modules\AppointmentTracking\Application\UseCases;

use App\Core\Authorization\AuthorizationServiceInterface;
use App\Core\Authorization\Exceptions\AuthorizationException;
use App\Modules\Appointments\Domain\Exceptions\AppointmentException;
use App\Modules\Appointments\Domain\Service\AppointmentsService;
use App\Modules\Appointments\Domain\ValueObjects\AppointmentId;
use App\Modules\AppointmentTracking\Application\DTOs\CompleteAppointmentDTO;
use App\Modules\AppointmentTracking\Application\Support\TrackingFactory;
use App\Modules\AppointmentTracking\Domain\Entities\AppointmentTrackingRecord;
use App\Modules\AppointmentTracking\Domain\Exceptions\AppointmentTrackingException;
use App\Modules\AppointmentTracking\Domain\Service\AppointmentCompletionService;
use App\Modules\AppointmentTracking\Domain\Service\AppointmentTrackingService;
use App\Modules\AppointmentTracking\Domain\ValueObjects\AppointmentTrackingAppointmentId;

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
        $completesAny = $this->authorization->can('appointment-tracking.create');

        if (! $completesAny && ! $this->authorization->can('appointment-tracking.create-own')) {
            $this->authorization->assertCan('appointment-tracking.create');
        }

        $appointmentId = new AppointmentId($dto->appointmentId);
        $appointment = $this->appointmentsService->findById($appointmentId);

        if (! $appointment) {
            throw AppointmentException::notFound($appointmentId);
        }

        // Spec 018 (CA13): the doctor only completes the appointments assigned to them,
        // checked before the appointment or its follow-up are touched.
        if (! $completesAny && $appointment->UserId()->value !== $this->authorization->currentActorId()) {
            throw AuthorizationException::forbidden('appointment-tracking.create-own');
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

        $tracking = TrackingFactory::tracking(
            appointmentId: $dto->appointmentId,
            reason: $dto->reason,
            symptoms: $dto->symptoms,
            diagnosis: $dto->diagnosis,
            procedurePerformed: $dto->procedurePerformed,
            observations: $dto->observations,
            recommendations: $dto->recommendations,
        );

        $prescriptions = TrackingFactory::prescriptions($tracking, $dto->prescriptions);

        return $this->appointmentCompletionService->complete($appointment, $tracking, $prescriptions);
    }
}
