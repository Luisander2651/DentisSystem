<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Aplication\UseCases;

use App\Core\Authorization\AuthorizationServiceInterface;
use App\Core\Domain\Pagination\Page;
use App\Core\Domain\Pagination\PageRequest;
use App\Modules\Appointments\Aplication\DTOs\GetAppointmentsByIdPatientDto;
use App\Modules\Appointments\Aplication\Exceptions\AppointmentAplicationExceptions;
use App\Modules\Appointments\Domain\Entities\AppointmentEntity;
use App\Modules\Appointments\Domain\Service\AppointmentsService;
use App\Modules\Patients\Domain\ValueObjects\Patients\PatientId;

/**
 * Spec 028 (CA2, CA14; P5): one page of the history of appointments of a patient for the staff,
 * the most recent first. A patient without appointments, or one that does not exist, gives an
 * empty page, as before.
 */
final readonly class ListPatientAppointmentsPageUseCase
{
    public const PAGE_SIZE = 10;

    public function __construct(
        private AuthorizationServiceInterface $authorization,
        private AppointmentsService $appointmentsService,
    ) {}

    /**
     * @return Page<AppointmentEntity>
     */
    public function execute(GetAppointmentsByIdPatientDto $dto, PageRequest $request): Page
    {
        $this->authorization->assertCan('appointments.patient-history.view');

        if ($dto->patientId === '') {
            throw AppointmentAplicationExceptions::invalidPatientId();
        }

        return $this->appointmentsService->findPageByPatient(new PatientId($dto->patientId), $request);
    }
}
