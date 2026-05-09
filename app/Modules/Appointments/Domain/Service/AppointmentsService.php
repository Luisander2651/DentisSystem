<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Domain\Service;

use App\Modules\Appointments\Domain\Entities\AppointmentEntity;
use App\Modules\Appointments\Domain\ValueObjects\AppointmentId;
use App\Modules\Appointments\Domain\ValueObjects\AppointmentStatus;
use App\Modules\Appointments\Domain\ValueObjects\AppointmentDate;
use App\Modules\Appointments\Domain\Repositories\AppointmentsRepositoryInterface;
use App\Modules\Patients\Domain\ValueObjects\Patients\PatientId;


class AppointmentsService
{
    public function __construct(
        private readonly AppointmentsRepositoryInterface $repository
    ) {}

    public function saveAppointment(AppointmentEntity $appointmentEntity): AppointmentEntity
    {
        return $this->repository->save($appointmentEntity);
    }

    public function findById(AppointmentId $id): ?AppointmentEntity
    {
        return $this->repository->findById($id);
    }

    public function findByStatusAndDate(?AppointmentStatus $status, ?AppointmentDate $date, ?PatientId $patientId = null): array
    {
        return $this->repository->findAllByStatusAndDateOrPatientId($status, $date, $patientId);
    }

    public function deleteAppointment(AppointmentId $id): void
    {
        $this->repository->delete($id);
    }
}