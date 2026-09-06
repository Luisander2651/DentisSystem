<?php

declare(strict_types=1);

namespace App\Modules\AppointmentTracking\Infrastructure\Persistence\Eloquent;

use App\Modules\Appointments\Domain\Entities\AppointmentEntity;
use App\Modules\Appointments\Domain\Repositories\AppointmentsRepositoryInterface;
use App\Modules\AppointmentTracking\Domain\Entities\AppointmentTrackingEntity;
use App\Modules\AppointmentTracking\Domain\Entities\AppointmentTrackingRecord;
use App\Modules\AppointmentTracking\Domain\Repositories\AppointmentCompletionRepositoryInterface;
use App\Modules\AppointmentTracking\Domain\Repositories\AppointmentTrackingRepositoryInterface;
use App\Modules\AppointmentTracking\Domain\Repositories\PrescriptionRepositoryInterface;
use Illuminate\Support\Facades\DB;

class EloquentAppointmentCompletionRepository implements AppointmentCompletionRepositoryInterface
{
    public function __construct(
        private readonly AppointmentsRepositoryInterface $appointments,
        private readonly AppointmentTrackingRepositoryInterface $appointmentTracking,
        private readonly PrescriptionRepositoryInterface $prescriptions,
    ) {}

    public function complete(
        AppointmentEntity $appointment,
        AppointmentTrackingEntity $appointmentTracking,
        array $prescriptions,
    ): AppointmentTrackingRecord {
        return DB::transaction(function () use ($appointment, $appointmentTracking, $prescriptions) {
            // 1. Confirms the appointment as completed, so it can no longer be modified.
            $this->appointments->save($appointment);

            // 2. Save the tracking information, which will be referenced by the prescriptions.
            $savedTracking = $this->appointmentTracking->save($appointmentTracking);

            // 3. Save the prescriptions, which will reference the tracking information.
            $savedPrescriptions = [];
            foreach ($prescriptions as $prescription) {
                $savedPrescriptions[] = $this->prescriptions->save($prescription);
            }

            // 4. Return a record of the completed appointment, including the saved tracking and prescriptions.
            return AppointmentTrackingRecord::create($savedTracking, $savedPrescriptions);
        });
    }
}
