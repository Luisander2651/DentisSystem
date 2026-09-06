<?php

declare(strict_types=1);

namespace App\Modules\AppointmentTracking\Domain\Entities;

final class AppointmentTrackingRecord
{
    private function __construct(
        private readonly AppointmentTrackingEntity $appointmentTracking,
        private readonly array $prescriptions,
    ) {}

    /**
     * @param  PrescriptionEntity[]  $prescriptions
     */
    public static function create(AppointmentTrackingEntity $appointmentTracking, array $prescriptions): self
    {
        return new self($appointmentTracking, $prescriptions);
    }

    public function AppointmentTracking(): AppointmentTrackingEntity
    {
        return $this->appointmentTracking;
    }

    /**
     * @return PrescriptionEntity[]
     */
    public function Prescriptions(): array
    {
        return $this->prescriptions;
    }
}
