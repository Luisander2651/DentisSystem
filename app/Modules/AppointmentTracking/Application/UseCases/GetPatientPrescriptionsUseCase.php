<?php

declare(strict_types=1);

namespace App\Modules\AppointmentTracking\Application\UseCases;

use App\Core\Authorization\AuthorizationServiceInterface;
use App\Modules\Appointments\Domain\Service\AppointmentsService;
use App\Modules\Appointments\Domain\ValueObjects\AppointmentId;
use App\Modules\AppointmentTracking\Application\DTOs\GetAppointmentTrackingByAppointmentIdDTO;
use App\Modules\AppointmentTracking\Domain\Entities\PrescriptionEntity;
use App\Modules\AppointmentTracking\Domain\Service\AppointmentTrackingService;
use App\Modules\AppointmentTracking\Domain\Service\PrescriptionService;
use App\Modules\AppointmentTracking\Domain\ValueObjects\AppointmentTrackingAppointmentId;
use App\Modules\AppointmentTracking\Domain\ValueObjects\PrescriptionAppointmentTrackingId;

/**
 * Spec 025 (CA5, CA6, CA8, CA12, CA17): the recommendations and prescriptions of an appointment
 * of the patient of the session. Ownership is checked first, so someone else's appointment reads
 * as missing whatever its status; only a completed appointment with follow-up has anything to
 * read, and nothing else of the follow-up leaves this use case.
 */
final readonly class GetPatientPrescriptionsUseCase
{
    public function __construct(
        private AuthorizationServiceInterface $authorization,
        private AppointmentsService $appointmentsService,
        private AppointmentTrackingService $appointmentTrackingService,
        private PrescriptionService $prescriptionService,
    ) {}

    /**
     * Null when the appointment does not exist or is not the patient's.
     *
     * @return array{recommendations: ?string, prescriptions: list<PrescriptionEntity>}|null
     */
    public function execute(GetAppointmentTrackingByAppointmentIdDTO $dto): ?array
    {
        $patientId = $this->authorization->currentPatientId();
        $appointment = $this->appointmentsService->findById(new AppointmentId($dto->appointmentId));

        if ($appointment === null || $appointment->PatientId()->value !== $patientId) {
            return null;
        }

        $nothing = ['recommendations' => null, 'prescriptions' => []];

        if (! $appointment->Status()->isCompleted()) {
            return $nothing;
        }

        $tracking = $this->appointmentTrackingService->findByAppointmentId(
            new AppointmentTrackingAppointmentId($appointment->Id()->value)
        );

        if ($tracking === null) {
            return $nothing;
        }

        return [
            'recommendations' => $tracking->Recommendations()?->value,
            'prescriptions' => array_values($this->prescriptionService->findAllByAppointmentTrackingId(
                new PrescriptionAppointmentTrackingId($tracking->Id()->value)
            )),
        ];
    }
}
