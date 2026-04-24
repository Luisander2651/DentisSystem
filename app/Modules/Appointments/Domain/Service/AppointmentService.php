<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Domain\Service;

use App\Modules\Appointments\Domain\Entities\AppointmentEntity;
use App\Modules\Appointments\Domain\ValueObjects\AppointmentId;
use App\Modules\Appointments\Domain\ValueObjects\AppointmentStatus;
use App\Modules\Appointments\Domain\ValueObjects\AppointmentDate;
use App\Modules\Appointments\Domain\Repositories\AppointmentsRepositoryInterface;

class AppointmentsService
{
    public function __construct(
        private readonly AppointmentsRepositoryInterface $repository
    ) {}

    public function saveAppointment(AppointmentEntity $appointmentEntity): void
    {
        $this->repository->save($appointmentEntity);
    }

    public function findById(AppointmentId $id): ?AppointmentEntity
    {
        return $this->repository->findById($id);
    }

    public function findByStatusAndDate(?AppointmentStatus $status, ?AppointmentDate $date): array
    {
        return $this->repository->findAllByStatusAndDate($status, $date);
    }

    public function deleteAppointment(AppointmentId $id): void
    {
        $this->repository->delete($id);
    }
}