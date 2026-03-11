<?php

namespace App\Modules\Patients\Domain\Repositories;

use App\Modules\Patients\Domain\Repositories\PatientRelatedRepository;
use App\Modules\Patients\Domain\Entities\Patient;
use App\Modules\Patients\Domain\ValueObjects\Patients\PatientId;
use App\Modules\Patients\Domain\ValueObjects\Patients\PatientStatus;
use App\Modules\Patients\Domain\ValueObjects\Patients\PatientRole;
use App\Modules\Patients\Domain\ValueObjects\Patients\PatientEmail;

interface PatientRepositoryInterface extends PatientRelatedRepository
{
    public function save(Patient $patient): void;

    public function delete(PatientId $patientId): void;

    public function findByRoleAndStatus(?PatientRole $role, ?PatientStatus $status): ?array;

    public function findByEmailExcludingId(PatientEmail $email, ?PatientId $excludedId): ?Patient;
}
