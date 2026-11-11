<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Aplication\UseCases;

use App\Core\Authorization\AuthorizationServiceInterface;
use App\Modules\Appointments\Aplication\DTOs\GetPatientNextAppointmentDTO;
use App\Modules\Appointments\Domain\Entities\AppointmentEntity;
use App\Modules\Appointments\Domain\Service\AppointmentsService;
use App\Modules\Appointments\Domain\ValueObjects\AppointmentDate;
use App\Modules\Patients\Domain\ValueObjects\Patients\PatientId;

/**
 * Spec 025 (CA1, CA2): the next appointment of the patient of the session is the assigned or
 * rescheduled one nearest from today on, in the clinic's time zone. One of today counts even
 * when its hour has gone by, so a patient who arrives late still has their QR code.
 */
final readonly class GetPatientNextAppointmentUseCase
{
    public function __construct(
        private AuthorizationServiceInterface $authorization,
        private AppointmentsService $appointmentsService,
    ) {}

    public function execute(GetPatientNextAppointmentDTO $dto): ?AppointmentEntity
    {
        $patientId = new PatientId($this->authorization->currentPatientId());
        $today = new AppointmentDate($dto->today);

        $next = null;

        foreach ($this->appointmentsService->findByStatusAndDate(null, null, $patientId) as $appointment) {
            $isOpen = $appointment->Status()->isAssigned() || $appointment->Status()->isRescheduled();

            if (! $isOpen || $appointment->Date()->value < $today->value) {
                continue;
            }

            if ($next === null || $this->startsAt($appointment) < $this->startsAt($next)) {
                $next = $appointment;
            }
        }

        return $next;
    }

    private function startsAt(AppointmentEntity $appointment): string
    {
        return $appointment->Date()->value.' '.$appointment->Time()->value;
    }
}
