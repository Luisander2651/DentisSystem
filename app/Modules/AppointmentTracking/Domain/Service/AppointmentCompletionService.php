<?php

declare(strict_types=1);

namespace App\Modules\AppointmentTracking\Domain\Service;

use App\Modules\Appointments\Domain\Entities\AppointmentEntity;
use App\Modules\AppointmentTracking\Domain\Entities\AppointmentTrackingEntity;
use App\Modules\AppointmentTracking\Domain\Entities\AppointmentTrackingRecord;
use App\Modules\AppointmentTracking\Domain\Entities\PrescriptionEntity;
use App\Modules\AppointmentTracking\Domain\Repositories\AppointmentCompletionRepositoryInterface;

class AppointmentCompletionService
{
    public function __construct(
        private readonly AppointmentCompletionRepositoryInterface $repository
    ) {}

    /**
     * @param  PrescriptionEntity[]  $prescriptions
     */
    public function complete(
        AppointmentEntity $appointment,
        AppointmentTrackingEntity $appointmentTracking,
        array $prescriptions,
    ): AppointmentTrackingRecord {
        return $this->repository->complete($appointment, $appointmentTracking, $prescriptions);
    }
}
