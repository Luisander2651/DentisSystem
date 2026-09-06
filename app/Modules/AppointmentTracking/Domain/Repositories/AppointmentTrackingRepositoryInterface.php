<?php

declare(strict_types=1);

namespace App\Modules\AppointmentTracking\Domain\Repositories;

use App\Modules\AppointmentTracking\Domain\Entities\AppointmentTrackingEntity;
use App\Modules\AppointmentTracking\Domain\ValueObjects\AppointmentTrackingAppointmentId;
use App\Modules\AppointmentTracking\Domain\ValueObjects\AppointmentTrackingId;

interface AppointmentTrackingRepositoryInterface
{
    public function save(AppointmentTrackingEntity $appointmentTracking): AppointmentTrackingEntity;

    public function findById(AppointmentTrackingId $id): ?AppointmentTrackingEntity;

    public function findByAppointmentId(AppointmentTrackingAppointmentId $appointmentId): ?AppointmentTrackingEntity;

    public function delete(AppointmentTrackingId $id): void;
}
