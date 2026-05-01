<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Aplication\UseCases;

use App\Modules\Appointments\Aplication\DTOs\GetAppointmentsByIdPatientDto;
use App\Modules\Appointments\Domain\Service\AppointmentsService;
use App\Modules\Appointments\Aplication\Exceptions\AppointmentAplicationExceptions;
use App\Modules\Patients\Domain\ValueObjects\Patients\PatientId;


final readonly class GetAppointentByPatientIdUseCase
{
    public function __construct(
        private AppointmentsService $appointmentsService,
    ) {}

    public function execute(GetAppointmentsByIdPatientDto $getAppointmentsByIdPatientDto): array
    {
        if ($getAppointmentsByIdPatientDto->patientId === '') {
            throw AppointmentAplicationExceptions::invalidPatientId();
        }
        $patientId = new PatientId($getAppointmentsByIdPatientDto->patientId);
        return $this->appointmentsService->findByStatusAndDate(null, null, $patientId);
    }
}
