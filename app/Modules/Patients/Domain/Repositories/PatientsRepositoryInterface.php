<?php

namespace App\Modules\Patients\Domain\Repositories;

use App\Core\Domain\Pagination\Page;
use App\Core\Domain\Pagination\PageRequest;
use App\Modules\Patients\Domain\Entities\Patient;
use App\Modules\Patients\Domain\ValueObjects\Patients\PatientEmail;
use App\Modules\Patients\Domain\ValueObjects\Patients\PatientId;
use App\Modules\Patients\Domain\ValueObjects\Patients\PatientStatus;

interface PatientsRepositoryInterface
{
    public function findByPatientId(PatientId $patientId): ?Patient;

    public function save(Patient $patient): void;

    public function delete(PatientId $patientId): void;

    public function findByRoleAndStatus(?PatientStatus $status): array;

    public function findByEmailExcludingId(PatientEmail $email, ?PatientId $excludedId): ?Patient;

    /**
     * Spec 028 (CA4, CA7, CA9): one page of the patients, newest first, filtered by status and by a
     * literal search over the first name, the last name, the full name and the email.
     *
     * @return Page<Patient>
     */
    public function findPage(PageRequest $request, ?PatientStatus $status, ?string $search): Page;

    /**
     * Spec 028 (CA5): how many patients the whole list holds, by status.
     *
     * @return array{total: int, active: int, inactive: int}
     */
    public function countByStatus(): array;
}
