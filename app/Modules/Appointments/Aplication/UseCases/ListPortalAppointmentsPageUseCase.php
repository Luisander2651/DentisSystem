<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Aplication\UseCases;

use App\Core\Authorization\AuthorizationServiceInterface;
use App\Core\Domain\Pagination\Page;
use App\Core\Domain\Pagination\PageRequest;
use App\Modules\Appointments\Domain\Entities\AppointmentEntity;
use App\Modules\Appointments\Domain\Service\AppointmentsService;
use App\Modules\Patients\Domain\ValueObjects\Patients\PatientId;

/**
 * Spec 028 (CA2, CA7, CA14): one page of the appointments of the patient of the session, the most
 * recent first, ordered by the database instead of in memory. The patient comes from the session,
 * never from the request (spec 025, D2).
 */
final readonly class ListPortalAppointmentsPageUseCase
{
    public const PAGE_SIZE = 10;

    public function __construct(
        private AuthorizationServiceInterface $authorization,
        private AppointmentsService $appointmentsService,
    ) {}

    /**
     * @return Page<AppointmentEntity>
     */
    public function execute(PageRequest $request): Page
    {
        $patientId = new PatientId($this->authorization->currentPatientId());

        return $this->appointmentsService->findPageByPatient($patientId, $request);
    }
}
