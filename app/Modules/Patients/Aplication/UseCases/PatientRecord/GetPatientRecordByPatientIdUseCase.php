<?php

declare(strict_types=1);

namespace App\Modules\Patients\Aplication\UseCases\PatientRecord;

use App\Modules\Patients\Aplication\DTOs\PatientRecord\GetPatientRecordByPatientIdDTO;
use App\Modules\Patients\Aplication\Exceptions\PatientRecord\PatientRecordAplicationExceptions;
use App\Modules\Patients\Domain\Entities\PatientRecord;
use App\Modules\Patients\Domain\Service\PatientRecordService;
use App\Modules\Patients\Domain\ValueObjects\Patients\PatientId;

final readonly class GetPatientRecordByPatientIdUseCase
{
    public function __construct(
        private PatientRecordService $patientRecordService,
    ) {}

    public function execute(GetPatientRecordByPatientIdDTO $dto): PatientRecord
    {
        if ($dto->patientId === '') {
            throw PatientRecordAplicationExceptions::IdNotProvided();
        }

        $patientRecord = $this->patientRecordService->getPatientRecordByPatientId(new PatientId($dto->patientId));

        if ($patientRecord === null) {
            throw PatientRecordAplicationExceptions::NotFound();
        }

        return $patientRecord;
    }
}
