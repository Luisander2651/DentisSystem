<?php

declare(strict_types=1);

namespace App\Modules\Patients\Domain\Service;

use App\Modules\Patients\Domain\Entities\PatientRecord;
use App\Modules\Patients\Domain\Repositories\PatientRecordRepositoryInterface;
use App\Modules\Patients\Domain\ValueObjects\Patients\PatientId;

class PatientRecordService
{
    private readonly PatientRecordRepositoryInterface $patientRecordRepository;

    public function __construct(PatientRecordRepositoryInterface $patientRecordRepository)
    {
        $this->patientRecordRepository = $patientRecordRepository;
    }

    public function getPatientRecordByPatientId(PatientId $patientId): ?PatientRecord
    {
        return $this->patientRecordRepository->GetByPatientId($patientId);
    }
}