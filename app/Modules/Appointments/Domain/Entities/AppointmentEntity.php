<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Domain\Entities;

use App\Modules\Appointments\Domain\ValueObjects\AppointmentId;
use App\Modules\Appointments\Domain\ValueObjects\AppointmentDate;
use App\Modules\Appointments\Domain\ValueObjects\AppointmentTime;
use App\Modules\Appointments\Domain\ValueObjects\AppointmentStatus;
use App\Modules\Appointments\Domain\ValueObjects\TreatmentId;
use App\Modules\Users\Domain\ValueObjects\UserId;
use App\Modules\Patients\Domain\ValueObjects\Patients\PatientId;

use DateTimeImmutable;
use Illuminate\Support\Facades\App;

final class AppointmentEntity
{
    private function __construct(
        private readonly AppointmentId $id,
        private AppointmentDate $date,
        private AppointmentTime $time,
        private bool $whatsappReminder,
        private AppointmentStatus $status,
        private readonly TreatmentId $treatmentId,
        private readonly UserId $userId,
        private readonly PatientId $patientId,
        private readonly DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
    ) {}

    public static function create(
        AppointmentDate $date,
        AppointmentTime $time,
        TreatmentId $treatmentId,
        UserId $userId,
        PatientId $patientId,
        bool $whatsappReminder = false,
    ): self {
        return new self(
            AppointmentId::random(),
            $date,
            $time,
            $whatsappReminder,
            AppointmentStatus::assigned(),
            $treatmentId,
            $userId,
            $patientId,
            new DateTimeImmutable(),
            new DateTimeImmutable()
        );
    }

    public static function fromPrimitives(
        string $id,
        string $date,
        string $time,
        bool $whatsappReminder,
        string $status,
        string $treatmentId,
        string $userId,
        string $patientId,
        string $createdAt,
        string $updatedAt
    ): self {
        return new self(
            new AppointmentId($id),
            new AppointmentDate($date),
            new AppointmentTime($time),
            $whatsappReminder,
            new AppointmentStatus($status),
            new TreatmentId($treatmentId),
            new UserId($userId),
            new PatientId($patientId),
            new DateTimeImmutable($createdAt),
            new DateTimeImmutable($updatedAt)
        );
    }

    public function reschedule(AppointmentDate $newDate, AppointmentTime $newTime): void
    {
        $this->date = $newDate;
        $this->time = $newTime;
        $this->status = AppointmentStatus::rescheduled();
        $this->updatedAt = new DateTimeImmutable();
    }

    public function complete(): void
    {
        $this->status = AppointmentStatus::completed();
        $this->updatedAt = new DateTimeImmutable();
    }

    public function cancel(): void
    {
        $this->status = AppointmentStatus::cancelled();
        $this->updatedAt = new DateTimeImmutable();
    }

    public function updateWhatsappReminder(bool $enabled): void
    {
        $this->whatsappReminder = $enabled;
        $this->updatedAt = new DateTimeImmutable();
    }

    // Getters
    public function Id(): AppointmentId
    {
        return $this->id;
    }

    public function Date(): AppointmentDate
    {
        return $this->date;
    }

    public function Time(): AppointmentTime
    {
        return $this->time;
    }

    public function WhatsappReminder(): bool
    {
        return $this->whatsappReminder;
    }

    public function Status(): AppointmentStatus
    {
        return $this->status;
    }

    public function TreatmentId(): TreatmentId
    {
        return $this->treatmentId;
    }

    public function UserId(): UserId
    {
        return $this->userId;
    }

    public function PatientId(): PatientId
    {
        return $this->patientId;
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
