<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Aplication\UseCases;

use App\Modules\Appointments\Aplication\DTOs\CreateAppointmentDTO;
use App\Modules\Appointments\Aplication\Exceptions\AppointmentScheduleConflictException;
use App\Modules\Appointments\Domain\Service\AppointmentsService;
use App\Modules\Appointments\Domain\Entities\AppointmentEntity;
use App\Modules\Appointments\Domain\ValueObjects\AppointmentDate;
use App\Modules\Appointments\Domain\ValueObjects\AppointmentStatus;
use App\Modules\Users\Domain\ValueObjects\UserId;
use App\Modules\Patients\Domain\ValueObjects\Patients\PatientId;
use App\Modules\Appointments\Domain\ValueObjects\AppointmentTime;
use App\Modules\Appointments\Domain\ValueObjects\TreatmentId;
use App\Modules\Appointments\Domain\Service\TreatmentsService;

final readonly class CreateAppointmentUseCase
{
    public function __construct(
        private AppointmentsService $appointmentsService,
        private TreatmentsService $treatmentsService,
    ) {}

    public function execute(CreateAppointmentDTO $createAppointmentDTO): AppointmentEntity
    {
        $this->validateScheduleAvailability($createAppointmentDTO);

        $appointment = AppointmentEntity::create(
            date: AppointmentDate::fromString($createAppointmentDTO->date),
            time: AppointmentTime::fromString($createAppointmentDTO->time),
            treatmentId: TreatmentId::fromInt((int)$createAppointmentDTO->treatmentId),
            userId: new UserId($createAppointmentDTO->userId),
            patientId: new PatientId($createAppointmentDTO->patientId),

        );

        return $this->appointmentsService->saveAppointment($appointment);
    }

    private function validateScheduleAvailability(CreateAppointmentDTO $createAppointmentDTO): void
    {
        $requestedDate = AppointmentDate::fromString($createAppointmentDTO->date);
        $requestedStart = $this->timeToMinutes($createAppointmentDTO->time);
        $treatmentDuration = $this->getTreatmentDurationMinutes((int) $createAppointmentDTO->treatmentId);

        $requestedEnd = $requestedStart + $treatmentDuration;

        $appointments = $this->appointmentsService->findByStatusAndDate(
            status: null,
            date: $requestedDate,
        );

        foreach ($appointments as $appointment) {
            if ($appointment->Status()->equals(AppointmentStatus::cancelled())) {
                continue;
            }

            $existingStart = $this->timeToMinutes($appointment->Time()->value);
            $existingDuration = (int) ($appointment->TreatmentTime()?->value ?? 0);

            if ($existingDuration <= 0) {
                continue;
            }

            $existingEnd = $existingStart + $existingDuration;

            if ($requestedStart < $existingEnd && $requestedEnd > $existingStart) {
                throw AppointmentScheduleConflictException::occupied($createAppointmentDTO->date, $createAppointmentDTO->time);
            }
        }
    }

    private function getTreatmentDurationMinutes(int $treatmentId): int
    {
        $treamentId = TreatmentId::fromInt($treatmentId);
        $treatment = $this->treatmentsService->findById($treamentId);

        return (int) ($treatment->Time()->value ?? 0);
    }

    private function timeToMinutes(string $time): int
    {
        [$hours, $minutes] = array_pad(explode(':', $time), 2, '0');

        return ((int) $hours * 60) + (int) $minutes;
    }
}