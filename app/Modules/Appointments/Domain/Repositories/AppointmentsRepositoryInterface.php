<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Domain\Repositories;

use App\Modules\Appointments\Domain\Entities\AppointmentEntity;
use App\Modules\Appointments\Domain\ValueObjects\AppointmentDate;
use App\Modules\Appointments\Domain\ValueObjects\AppointmentId;
use App\Modules\Appointments\Domain\ValueObjects\AppointmentPage;
use App\Modules\Appointments\Domain\ValueObjects\AppointmentStatus;
use App\Modules\Appointments\Domain\ValueObjects\AppointmentUserId;
use App\Modules\Patients\Domain\ValueObjects\Patients\PatientId;

interface AppointmentsRepositoryInterface
{
    public function save(AppointmentEntity $appointment): AppointmentEntity;

    public function findById(AppointmentId $id): ?AppointmentEntity;

    /**
     * Spec 018: `$userId` narrows the list to the appointments of one doctor.
     */
    public function findAllByStatusAndDateOrPatientId(?AppointmentStatus $status, ?AppointmentDate $date, ?PatientId $patientId = null, ?AppointmentUserId $userId = null): array;

    public function delete(AppointmentId $id): void;

    /**
     * Spec 020 (CA4, TM8): the appointments of days before `$today` still assigned or
     * rescheduled, from the most recent to the oldest, at most `$limit`, and the total of all.
     */
    public function findUnclosedBefore(AppointmentDate $today, ?AppointmentUserId $userId, int $limit): AppointmentPage;
}
