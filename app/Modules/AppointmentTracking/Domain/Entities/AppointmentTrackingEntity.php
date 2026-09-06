<?php

declare(strict_types=1);

namespace App\Modules\AppointmentTracking\Domain\Entities;

use App\Modules\AppointmentTracking\Domain\ValueObjects\AppointmentTrackingAppointmentId;
use App\Modules\AppointmentTracking\Domain\ValueObjects\AppointmentTrackingId;
use App\Modules\AppointmentTracking\Domain\ValueObjects\Diagnosis;
use App\Modules\AppointmentTracking\Domain\ValueObjects\Observations;
use App\Modules\AppointmentTracking\Domain\ValueObjects\ProcedurePerformed;
use App\Modules\AppointmentTracking\Domain\ValueObjects\Reason;
use App\Modules\AppointmentTracking\Domain\ValueObjects\Recommendations;
use App\Modules\AppointmentTracking\Domain\ValueObjects\Symptoms;
use DateTimeImmutable;

final class AppointmentTrackingEntity
{
    private function __construct(
        private readonly AppointmentTrackingId $id,
        private readonly AppointmentTrackingAppointmentId $appointmentId,
        private Reason $reason,
        private Symptoms $symptoms,
        private Diagnosis $diagnosis,
        private ProcedurePerformed $procedurePerformed,
        private ?Observations $observations,
        private ?Recommendations $recommendations,
        private readonly DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
    ) {}

    public static function create(
        AppointmentTrackingAppointmentId $appointmentId,
        Reason $reason,
        Symptoms $symptoms,
        Diagnosis $diagnosis,
        ProcedurePerformed $procedurePerformed,
        ?Observations $observations,
        ?Recommendations $recommendations,
    ): self {
        return new self(
            AppointmentTrackingId::random(),
            $appointmentId,
            $reason,
            $symptoms,
            $diagnosis,
            $procedurePerformed,
            $observations,
            $recommendations,
            new DateTimeImmutable,
            new DateTimeImmutable,
        );
    }

    public static function fromPrimitives(
        string $id,
        string $appointmentId,
        string $reason,
        array $symptoms,
        string $diagnosis,
        string $procedurePerformed,
        ?string $observations,
        ?string $recommendations,
        string $createdAt,
        string $updatedAt,
    ): self {
        return new self(
            new AppointmentTrackingId($id),
            new AppointmentTrackingAppointmentId($appointmentId),
            Reason::fromString($reason),
            Symptoms::fromArray($symptoms),
            Diagnosis::fromString($diagnosis),
            ProcedurePerformed::fromString($procedurePerformed),
            Observations::fromNullable($observations),
            Recommendations::fromNullable($recommendations),
            new DateTimeImmutable($createdAt),
            new DateTimeImmutable($updatedAt),
        );
    }

    public function update(
        ?Reason $reason = null,
        ?Symptoms $symptoms = null,
        ?Diagnosis $diagnosis = null,
        ?ProcedurePerformed $procedurePerformed = null,
        ?Observations $observations = null,
        ?Recommendations $recommendations = null,
    ): void {
        if ($reason !== null) {
            $this->reason = $reason;
        }
        if ($symptoms !== null) {
            $this->symptoms = $symptoms;
        }
        if ($diagnosis !== null) {
            $this->diagnosis = $diagnosis;
        }
        if ($procedurePerformed !== null) {
            $this->procedurePerformed = $procedurePerformed;
        }
        if ($observations !== null) {
            $this->observations = $observations;
        }
        if ($recommendations !== null) {
            $this->recommendations = $recommendations;
        }

        $this->updatedAt = new DateTimeImmutable;
    }

    // Getters
    public function Id(): AppointmentTrackingId
    {
        return $this->id;
    }

    public function AppointmentId(): AppointmentTrackingAppointmentId
    {
        return $this->appointmentId;
    }

    public function Reason(): Reason
    {
        return $this->reason;
    }

    public function Symptoms(): Symptoms
    {
        return $this->symptoms;
    }

    public function Diagnosis(): Diagnosis
    {
        return $this->diagnosis;
    }

    public function ProcedurePerformed(): ProcedurePerformed
    {
        return $this->procedurePerformed;
    }

    public function Observations(): ?Observations
    {
        return $this->observations;
    }

    public function Recommendations(): ?Recommendations
    {
        return $this->recommendations;
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
