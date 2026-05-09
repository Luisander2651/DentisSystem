<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Domain\Repositories;

use App\Modules\Appointments\Domain\Entities\AppointmentEntity;
use App\Modules\Appointments\Domain\ValueObjects\AppointmentId;
use App\Modules\Appointments\Domain\ValueObjects\AppointmentDate;
use App\Modules\Appointments\Domain\ValueObjects\AppointmentStatus;
use App\Modules\Patients\Domain\ValueObjects\Patients\PatientId;



interface AppointmentsRepositoryInterface
{
    public function save(AppointmentEntity $appointment): AppointmentEntity;

    public function findById(AppointmentId $id): ?AppointmentEntity;

    public function findAllByStatusAndDateOrPatientId(?AppointmentStatus $status, ?AppointmentDate $date, ?PatientId $patientId = null): array;

    public function delete(AppointmentId $id): void;

}
