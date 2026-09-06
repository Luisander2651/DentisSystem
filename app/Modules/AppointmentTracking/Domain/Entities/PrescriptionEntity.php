<?php

declare(strict_types=1);

namespace App\Modules\AppointmentTracking\Domain\Entities;

use App\Modules\AppointmentTracking\Domain\ValueObjects\DailyFrequency;
use App\Modules\AppointmentTracking\Domain\ValueObjects\Dosage;
use App\Modules\AppointmentTracking\Domain\ValueObjects\DurationDays;
use App\Modules\AppointmentTracking\Domain\ValueObjects\Instructions;
use App\Modules\AppointmentTracking\Domain\ValueObjects\Medication;
use App\Modules\AppointmentTracking\Domain\ValueObjects\PrescriptionAppointmentTrackingId;
use App\Modules\AppointmentTracking\Domain\ValueObjects\PrescriptionId;
use DateTimeImmutable;

final class PrescriptionEntity
{
    private function __construct(
        private readonly PrescriptionId $id,
        private readonly PrescriptionAppointmentTrackingId $appointmentTrackingId,
        private Medication $medication,
        private Dosage $dosage,
        private DurationDays $durationDays,
        private DailyFrequency $dailyFrequency,
        private ?Instructions $instructions,
        private readonly DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
    ) {}

    public static function create(
        PrescriptionAppointmentTrackingId $appointmentTrackingId,
        Medication $medication,
        Dosage $dosage,
        DurationDays $durationDays,
        DailyFrequency $dailyFrequency,
        ?Instructions $instructions,
    ): self {
        return new self(
            PrescriptionId::random(),
            $appointmentTrackingId,
            $medication,
            $dosage,
            $durationDays,
            $dailyFrequency,
            $instructions,
            new DateTimeImmutable,
            new DateTimeImmutable,
        );
    }

    public static function fromPrimitives(
        string $id,
        string $appointmentTrackingId,
        string $medication,
        string $dosage,
        int $durationDays,
        int $dailyFrequency,
        ?string $instructions,
        string $createdAt,
        string $updatedAt,
    ): self {
        return new self(
            new PrescriptionId($id),
            new PrescriptionAppointmentTrackingId($appointmentTrackingId),
            Medication::fromString($medication),
            Dosage::fromString($dosage),
            DurationDays::fromInt($durationDays),
            DailyFrequency::fromInt($dailyFrequency),
            Instructions::fromNullable($instructions),
            new DateTimeImmutable($createdAt),
            new DateTimeImmutable($updatedAt),
        );
    }

    public function update(
        ?Medication $medication = null,
        ?Dosage $dosage = null,
        ?DurationDays $durationDays = null,
        ?DailyFrequency $dailyFrequency = null,
        ?Instructions $instructions = null,
    ): void {
        if ($medication !== null) {
            $this->medication = $medication;
        }
        if ($dosage !== null) {
            $this->dosage = $dosage;
        }
        if ($durationDays !== null) {
            $this->durationDays = $durationDays;
        }
        if ($dailyFrequency !== null) {
            $this->dailyFrequency = $dailyFrequency;
        }
        if ($instructions !== null) {
            $this->instructions = $instructions;
        }

        $this->updatedAt = new DateTimeImmutable;
    }

    // Getters
    public function Id(): PrescriptionId
    {
        return $this->id;
    }

    public function AppointmentTrackingId(): PrescriptionAppointmentTrackingId
    {
        return $this->appointmentTrackingId;
    }

    public function Medication(): Medication
    {
        return $this->medication;
    }

    public function Dosage(): Dosage
    {
        return $this->dosage;
    }

    public function DurationDays(): DurationDays
    {
        return $this->durationDays;
    }

    public function DailyFrequency(): DailyFrequency
    {
        return $this->dailyFrequency;
    }

    public function Instructions(): ?Instructions
    {
        return $this->instructions;
    }

    public function CreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function UpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
