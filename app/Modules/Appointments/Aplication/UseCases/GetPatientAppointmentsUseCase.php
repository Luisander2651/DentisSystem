<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Aplication\UseCases;

use App\Core\Authorization\AuthorizationServiceInterface;
use App\Modules\Appointments\Aplication\DTOs\GetPatientAppointmentsDTO;
use App\Modules\Appointments\Domain\Entities\AppointmentEntity;
use App\Modules\Appointments\Domain\Service\AppointmentsService;
use App\Modules\Patients\Domain\ValueObjects\Patients\PatientId;

/**
 * Spec 025 (CA3, CA13): every appointment of the patient of the session, from the most recent
 * to the oldest. The patient comes from the session, never from the request (D2).
 */
final readonly class GetPatientAppointmentsUseCase
{
    public function __construct(
        private AuthorizationServiceInterface $authorization,
        private AppointmentsService $appointmentsService,
    ) {}

    /**
     * @return list<AppointmentEntity>
     */
    public function execute(GetPatientAppointmentsDTO $dto): array
    {
        $patientId = new PatientId($this->authorization->currentPatientId());

        $appointments = array_values($this->appointmentsService->findByStatusAndDate(null, null, $patientId));

        usort($appointments, fn (AppointmentEntity $a, AppointmentEntity $b): int => strcmp(
            $b->Date()->value.' '.$b->Time()->value,
            $a->Date()->value.' '.$a->Time()->value,
        ));

        return $appointments;
    }
}
