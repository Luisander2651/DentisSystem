<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Domain\Repositories;

use App\Modules\Appointments\Domain\Entities\AppointmentEntity;
use App\Modules\Appointments\Domain\ValueObjects\AppointmentId;
use App\Modules\Appointments\Domain\ValueObjects\AppointmentDate;
use App\Modules\Appointments\Domain\ValueObjects\AppointmentStatus;

interface AppointmentsRepositoryInterface
{
    public function save(AppointmentEntity $appointment): void;

    public function findById(AppointmentId $id): ?AppointmentEntity;

    public function findAllByStatusAndDate(?AppointmentStatus $status, ?AppointmentDate $date): array;

    public function delete(AppointmentId $id): void;

}
