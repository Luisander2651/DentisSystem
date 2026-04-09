<?php

declare(strict_types=1);

namespace App\Modules\Patients\Domain\Service;

use App\Modules\Patients\Domain\Entities\MedicalData;
use App\Modules\Patients\Domain\Repositories\MedicalDataRepositoryInterface;
use App\Modules\Patients\Domain\ValueObjects\Patients\PatientId;

class MedicalDataService
{
    private readonly MedicalDataRepositoryInterface $medicalDataRepository;

    public function __construct(MedicalDataRepositoryInterface $medicalDataRepository)
    {
        $this->medicalDataRepository = $medicalDataRepository;
    }

    public function saveOrUpdateMedicalData(MedicalData $medicalData): void
    {
        $this->medicalDataRepository->save($medicalData);
    }

    public function deleteMedicalData(PatientId $patientId): void
    {
        $this->medicalDataRepository->deleteByPatientId($patientId);
    }
}