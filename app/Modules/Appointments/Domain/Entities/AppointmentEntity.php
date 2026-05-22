<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Domain\Entities;

use App\Modules\Appointments\Domain\Exceptions\AppointmentException;
use App\Modules\Appointments\Domain\ValueObjects\AppointmentId;
use App\Modules\Appointments\Domain\ValueObjects\AppointmentDate;
use App\Modules\Appointments\Domain\ValueObjects\AppointmentTime;
use App\Modules\Appointments\Domain\ValueObjects\AppointmentStatus;
use App\Modules\Appointments\Domain\ValueObjects\AppointmentWhatsAppReminder;
use App\Modules\Appointments\Domain\ValueObjects\TreatmentId;
use App\Modules\Users\Domain\ValueObjects\UserId;
use App\Modules\Patients\Domain\ValueObjects\Patients\PatientId;
use App\Modules\Users\Domain\ValueObjects\UserName;
use App\Modules\Patients\Domain\ValueObjects\Patients\PatientName;
use App\Modules\Appointments\Domain\ValueObjects\TreatmentName;

use DateTimeImmutable;

final class AppointmentEntity
{
    private function __construct(
        private readonly AppointmentId $id,
        private AppointmentDate $date,
        private AppointmentTime $time,
        private AppointmentStatus $status,
        private AppointmentWhatsAppReminder $whatsAppReminder,
        private readonly TreatmentId $treatmentId,
        private readonly UserId $userId,
        private readonly PatientId $patientId,
        private readonly ?TreatmentName $treatmentName,
        private readonly ?UserName $userName,
        private readonly ?PatientName $patientName,
        private readonly DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
    ) {}

    public static function create(
        AppointmentDate $date,
        AppointmentTime $time,
        TreatmentId $treatmentId,
        UserId $userId,
        PatientId $patientId,
    ): self {
        return new self(
            AppointmentId::random(),
            $date,
            $time,
            AppointmentStatus::assigned(),
            AppointmentWhatsAppReminder::default(),
            $treatmentId,
            $userId,
            $patientId,
            null,
            null,
            null,
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
        ?string $treatmentName,
        ?string $userName,
        ?string $patientName,
        string $createdAt,
        string $updatedAt
    ): self {
        return new self(
            new AppointmentId($id),
            new AppointmentDate($date),
            new AppointmentTime($time),
            new AppointmentStatus($status),
            AppointmentWhatsAppReminder::fromBool($whatsappReminder),
            new TreatmentId($treatmentId),
            new UserId($userId),
            new PatientId($patientId),
            $treatmentName ? new TreatmentName($treatmentName) : null,
            $userName ? UserName::fromString($userName) : null,
            $patientName ? PatientName::fromString($patientName) : null,
            new DateTimeImmutable($createdAt),
            new DateTimeImmutable($updatedAt)
        );
    }

    public function reschedule(?AppointmentDate $newDate, ?AppointmentTime $newTime): void
    {
        if ($newDate === null && $newTime === null) {
            throw AppointmentException::rescheduleRequiresDateOrTime();
        }

        $newDate !== null && $this->date = $newDate;
        $newTime !== null && $this->time = $newTime;
        $this->status !== AppointmentStatus::rescheduled() && $this->status = AppointmentStatus::rescheduled();
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

    public function updateWhatsappReminder(): void
    {
        $this->whatsAppReminder = AppointmentWhatsAppReminder::fromBool(!$this->whatsAppReminder->value());
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
        return $this->whatsAppReminder->value();
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

    public function TreatmentName(): ?TreatmentName
    {
        return $this->treatmentName;
    }

    public function UserName(): ?UserName
    {
        return $this->userName;
    }

    public function PatientName(): ?PatientName
    {
        return $this->patientName;
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
