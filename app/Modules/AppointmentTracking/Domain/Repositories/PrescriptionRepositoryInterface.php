<?php

declare(strict_types=1);

namespace App\Modules\AppointmentTracking\Domain\Repositories;

use App\Modules\AppointmentTracking\Domain\Entities\PrescriptionEntity;
use App\Modules\AppointmentTracking\Domain\ValueObjects\PrescriptionAppointmentTrackingId;
use App\Modules\AppointmentTracking\Domain\ValueObjects\PrescriptionId;

interface PrescriptionRepositoryInterface
{
    public function save(PrescriptionEntity $prescription): PrescriptionEntity;

    public function findById(PrescriptionId $id): ?PrescriptionEntity;

    /**
     * @return PrescriptionEntity[]
     */
    public function findAllByAppointmentTrackingId(PrescriptionAppointmentTrackingId $appointmentTrackingId): array;

    public function delete(PrescriptionId $id): void;
}
