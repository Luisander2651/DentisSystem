<?php

declare(strict_types=1);

namespace App\Modules\AppointmentTracking\Domain\Repositories;

use App\Modules\Appointments\Domain\Entities\AppointmentEntity;
use App\Modules\AppointmentTracking\Domain\Entities\AppointmentTrackingEntity;
use App\Modules\AppointmentTracking\Domain\Entities\AppointmentTrackingRecord;
use App\Modules\AppointmentTracking\Domain\Entities\PrescriptionEntity;

interface AppointmentCompletionRepositoryInterface
{
    /**
     * Persists, in a single atomic operation, the appointment status transition
     * together with its clinical tracking record and prescription lines.
     *
     * The appointment must already be transitioned in memory (e.g. via
     * AppointmentEntity::complete()) before being passed in here; this
     * repository is only responsible for persisting the three writes
     * atomically, not for applying domain rules.
     *
     * @param  PrescriptionEntity[]  $prescriptions
     */
    public function complete(
        AppointmentEntity $appointment,
        AppointmentTrackingEntity $appointmentTracking,
        array $prescriptions,
    ): AppointmentTrackingRecord;
}
