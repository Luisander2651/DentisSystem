<?php

declare(strict_types=1);

namespace App\Modules\AppointmentTracking\Domain\Service;

use App\Modules\AppointmentTracking\Domain\Entities\PrescriptionEntity;
use App\Modules\AppointmentTracking\Domain\Repositories\PrescriptionRepositoryInterface;
use App\Modules\AppointmentTracking\Domain\ValueObjects\PrescriptionAppointmentTrackingId;
use App\Modules\AppointmentTracking\Domain\ValueObjects\PrescriptionId;

class PrescriptionService
{
    public function __construct(
        private readonly PrescriptionRepositoryInterface $repository
    ) {}

    public function savePrescription(PrescriptionEntity $prescription): PrescriptionEntity
    {
        return $this->repository->save($prescription);
    }

    public function findById(PrescriptionId $id): ?PrescriptionEntity
    {
        return $this->repository->findById($id);
    }

    /**
     * @return PrescriptionEntity[]
     */
    public function findAllByAppointmentTrackingId(PrescriptionAppointmentTrackingId $appointmentTrackingId): array
    {
        return $this->repository->findAllByAppointmentTrackingId($appointmentTrackingId);
    }

    public function deletePrescription(PrescriptionId $id): void
    {
        $this->repository->delete($id);
    }
}
