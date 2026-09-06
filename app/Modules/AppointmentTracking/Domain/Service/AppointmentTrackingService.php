<?php

declare(strict_types=1);

namespace App\Modules\AppointmentTracking\Domain\Service;

use App\Modules\AppointmentTracking\Domain\Entities\AppointmentTrackingEntity;
use App\Modules\AppointmentTracking\Domain\Repositories\AppointmentTrackingRepositoryInterface;
use App\Modules\AppointmentTracking\Domain\ValueObjects\AppointmentTrackingAppointmentId;
use App\Modules\AppointmentTracking\Domain\ValueObjects\AppointmentTrackingId;

class AppointmentTrackingService
{
    public function __construct(
        private readonly AppointmentTrackingRepositoryInterface $repository
    ) {}

    public function saveAppointmentTracking(AppointmentTrackingEntity $appointmentTracking): AppointmentTrackingEntity
    {
        return $this->repository->save($appointmentTracking);
    }

    public function findById(AppointmentTrackingId $id): ?AppointmentTrackingEntity
    {
        return $this->repository->findById($id);
    }

    public function findByAppointmentId(AppointmentTrackingAppointmentId $appointmentId): ?AppointmentTrackingEntity
    {
        return $this->repository->findByAppointmentId($appointmentId);
    }

    public function deleteAppointmentTracking(AppointmentTrackingId $id): void
    {
        $this->repository->delete($id);
    }
}
