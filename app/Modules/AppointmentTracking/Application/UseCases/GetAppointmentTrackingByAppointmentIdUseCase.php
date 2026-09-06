<?php

declare(strict_types=1);

namespace App\Modules\AppointmentTracking\Application\UseCases;

use App\Core\Authorization\AuthorizationServiceInterface;
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
        private AppointmentTrackingService $appointmentTrackingService,
        private PrescriptionService $prescriptionService,
    ) {}

    public function execute(GetAppointmentTrackingByAppointmentIdDTO $dto): ?AppointmentTrackingRecord
    {
        $this->authorization->assertCan('appointment-tracking.view');

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
}
