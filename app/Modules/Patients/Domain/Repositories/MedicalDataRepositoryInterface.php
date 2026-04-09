<?php

namespace App\Modules\Patients\Domain\Repositories;

use App\Modules\Patients\Domain\Entities\MedicalData;
use App\Modules\Patients\Domain\ValueObjects\Patients\PatientId;


interface MedicalDataRepositoryInterface
{
    public function save(MedicalData $medicalData): void;

    public function findByPatientId(PatientId $patientId): ?MedicalData;

    public function deleteByPatientId(PatientId $patientId): void;
}