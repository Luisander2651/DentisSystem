<?php

declare(strict_types=1);

namespace App\Modules\AppointmentTracking\Application\UseCases;

use App\Core\Authorization\AuthorizationServiceInterface;
use App\Core\Authorization\Exceptions\AuthorizationException;
use App\Modules\Appointments\Domain\Service\AppointmentsService;
use App\Modules\Appointments\Domain\ValueObjects\AppointmentId;
use App\Modules\AppointmentTracking\Application\DTOs\CompleteAppointmentDTO;
use App\Modules\AppointmentTracking\Application\Support\TrackingFactory;
use App\Modules\AppointmentTracking\Domain\Entities\AppointmentTrackingRecord;
use App\Modules\AppointmentTracking\Domain\Exceptions\TrackingRegistrationException;
use App\Modules\AppointmentTracking\Domain\Service\AppointmentCompletionService;
use App\Modules\AppointmentTracking\Domain\Service\AppointmentTrackingService;
use App\Modules\AppointmentTracking\Domain\ValueObjects\AppointmentTrackingAppointmentId;

/**
 * Spec 020 (CA8, CA15, TM2, TM3, TM5): registers the clinical follow-up of an appointment that is
 * already completed and has none. Who may do it is checked as when completing - the administrator,
 * or the doctor on their own appointment - before anything is read; the appointment stays
 * completada.
 */
final readonly class RegisterAppointmentTrackingUseCase
{
    public function __construct(
        private AuthorizationServiceInterface $authorization,
        private AppointmentsService $appointmentsService,
        private AppointmentTrackingService $appointmentTrackingService,
        private AppointmentCompletionService $appointmentCompletionService,
    ) {}

    public function execute(CompleteAppointmentDTO $dto): AppointmentTrackingRecord
    {
        $registersAny = $this->authorization->can('appointment-tracking.create');

        if (! $registersAny && ! $this->authorization->can('appointment-tracking.create-own')) {
            $this->authorization->assertCan('appointment-tracking.create');
        }

        $appointment = $this->appointmentsService->findById(new AppointmentId($dto->appointmentId));

        if ($appointment === null) {
            throw TrackingRegistrationException::appointmentNotFound();
        }

        if (! $registersAny && $appointment->UserId()->value !== $this->authorization->currentActorId()) {
            throw AuthorizationException::forbidden('appointment-tracking.create-own');
        }

        if (! $appointment->Status()->isCompleted()) {
            throw TrackingRegistrationException::notCompleted();
        }

        $existing = $this->appointmentTrackingService->findByAppointmentId(new AppointmentTrackingAppointmentId($dto->appointmentId));

        if ($existing !== null) {
            throw TrackingRegistrationException::alreadyRegistered();
        }

        $tracking = TrackingFactory::tracking(
            appointmentId: $dto->appointmentId,
            reason: $dto->reason,
            symptoms: $dto->symptoms,
            diagnosis: $dto->diagnosis,
            procedurePerformed: $dto->procedurePerformed,
            observations: $dto->observations,
            recommendations: $dto->recommendations,
        );

        return $this->appointmentCompletionService->register($tracking, TrackingFactory::prescriptions($tracking, $dto->prescriptions));
    }
}
