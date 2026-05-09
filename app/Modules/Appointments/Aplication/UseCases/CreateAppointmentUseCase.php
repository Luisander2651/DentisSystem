<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Aplication\UseCases;

use App\Modules\Appointments\Aplication\DTOs\CreateAppointmentDTO;
use App\Modules\Appointments\Domain\Service\AppointmentsService;
use App\Modules\Appointments\Domain\Entities\AppointmentEntity;
use App\Modules\Appointments\Domain\ValueObjects\AppointmentDate;
use App\Modules\Users\Domain\ValueObjects\UserId;
use App\Modules\Patients\Domain\ValueObjects\Patients\PatientId;
use App\Modules\Appointments\Domain\ValueObjects\AppointmentTime;
use App\Modules\Appointments\Domain\ValueObjects\TreatmentId;

final readonly class CreateAppointmentUseCase
{
    public function __construct(
        private AppointmentsService $appointmentsService,
    ) {}

    public function execute(CreateAppointmentDTO $createAppointmentDTO): AppointmentEntity
    {

        $appointment = AppointmentEntity::create(
            date: AppointmentDate::fromString($createAppointmentDTO->date),
            time: AppointmentTime::fromString($createAppointmentDTO->time),
            treatmentId: TreatmentId::fromInt((int)$createAppointmentDTO->treatmentId),
            userId: new UserId($createAppointmentDTO->userId),
            patientId: new PatientId($createAppointmentDTO->patientId),

        );

        return $this->appointmentsService->saveAppointment($appointment);
    }
}