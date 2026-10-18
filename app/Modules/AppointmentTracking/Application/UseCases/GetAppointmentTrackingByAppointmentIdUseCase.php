<?php

declare(strict_types=1);

namespace App\Modules\AppointmentTracking\Application\UseCases;

use App\Core\Authorization\AuthorizationServiceInterface;
use App\Core\Authorization\Exceptions\AuthorizationException;
use App\Modules\Appointments\Domain\Service\AppointmentsService;
use App\Modules\Appointments\Domain\ValueObjects\AppointmentId;
use App\Modules\AppointmentTracking\Application\DTOs\GetAppointmentTrackingByAppointmentIdDTO;
use App\Modules\AppointmentTracking\Domain\Entities\AppointmentTrackingRecord;
use App\Modules\AppointmentTracking\Domain\Service\AppointmentTrackingService;
use App\Modules\AppointmentTracking\Domain\Service\PrescriptionService;
use App\Modules\AppointmentTracking\Domain\ValueObjects\AppointmentTrackingAppointmentId;
use App\Modules\AppointmentTracking\Domain\ValueObjects\PrescriptionAppointmentTrackingId;

final readonly class GetAppointmentTrackingByAppointmentIdUseCase
{
    public function __construct(
        private AuthorizationServiceInterface $authorization,
        private AppointmentsService $appointmentsService,
        private AppointmentTrackingService $appointmentTrackingService,
        private PrescriptionService $prescriptionService,
    ) {}

    public function execute(GetAppointmentTrackingByAppointmentIdDTO $dto): ?AppointmentTrackingRecord
    {
        $readsAny = $this->authorization->can('appointment-tracking.view');

        if (! $readsAny) {
            $this->assertOwnAppointment($dto->appointmentId);
        }

        $appointmentId = new AppointmentTrackingAppointmentId($dto->appointmentId);
        $tracking = $this->appointmentTrackingService->findByAppointmentId($appointmentId);

        if ($tracking === null) {
            return null;
        }

        $prescriptions = $this->prescriptionService->findAllByAppointmentTrackingId(
            new PrescriptionAppointmentTrackingId($tracking->Id()->value)
        );

        return AppointmentTrackingRecord::create($tracking, $prescriptions);
    }

    /**
     * Spec 018 (CA9, CA10): the doctor only reads the follow-up of the appointments assigned to
     * them. A missing appointment falls through to "no follow-up" (404), as for the
     * administrator; its existence is not secret (014).
     */
    private function assertOwnAppointment(string $appointmentId): void
    {
        if (! $this->authorization->can('appointment-tracking.view-own')) {
            $this->authorization->assertCan('appointment-tracking.view');
        }

        $appointment = $this->appointmentsService->findById(new AppointmentId($appointmentId));

        if ($appointment !== null && $appointment->UserId()->value !== $this->authorization->currentActorId()) {
            throw AuthorizationException::forbidden('appointment-tracking.view-own');
        }
    }
}
