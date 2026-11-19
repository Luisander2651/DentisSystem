<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Domain\Service;

use App\Core\Domain\Pagination\Page;
use App\Core\Domain\Pagination\PageRequest;
use App\Modules\Appointments\Domain\Entities\AppointmentEntity;
use App\Modules\Appointments\Domain\Repositories\AppointmentsRepositoryInterface;
use App\Modules\Appointments\Domain\ValueObjects\AppointmentDate;
use App\Modules\Appointments\Domain\ValueObjects\AppointmentId;
use App\Modules\Appointments\Domain\ValueObjects\AppointmentStatus;
use App\Modules\Appointments\Domain\ValueObjects\AppointmentUserId;
use App\Modules\Patients\Domain\ValueObjects\Patients\PatientId;

class AppointmentsService
{
    /**
     * Spec 028 (CA2, A28): the sections of the start show ten appointments per page.
     */
    public const PENDING_PAGE_SIZE = 10;

    public function __construct(
        private readonly AppointmentsRepositoryInterface $repository
    ) {}

    public function saveAppointment(AppointmentEntity $appointmentEntity): AppointmentEntity
    {
        return $this->repository->save($appointmentEntity);
    }

    public function findById(AppointmentId $id): ?AppointmentEntity
    {
        return $this->repository->findById($id);
    }

    public function findByStatusAndDate(?AppointmentStatus $status, ?AppointmentDate $date, ?PatientId $patientId = null, ?AppointmentUserId $userId = null): array
    {
        return $this->repository->findAllByStatusAndDateOrPatientId($status, $date, $patientId, $userId);
    }

    /**
     * @return Page<AppointmentEntity>
     */
    public function findPageByPatient(PatientId $patientId, PageRequest $request): Page
    {
        return $this->repository->findPageByPatient($patientId, $request);
    }

    public function deleteAppointment(AppointmentId $id): void
    {
        $this->repository->delete($id);
    }

    /**
     * Spec 028 (CA2, A28): one page of ten of the unclosed appointments of earlier days.
     *
     * @return Page<AppointmentEntity>
     */
    public function findUnclosed(AppointmentDate $today, ?AppointmentUserId $userId, int $page = 1): Page
    {
        return $this->repository->findUnclosedBefore($today, $userId, PageRequest::create($page, self::PENDING_PAGE_SIZE));
    }
}
