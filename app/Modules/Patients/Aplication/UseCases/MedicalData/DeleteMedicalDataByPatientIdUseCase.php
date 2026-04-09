<?php

declare(strict_types=1);

namespace App\Modules\Patients\Aplication\UseCases\MedicalData;

use App\Modules\Patients\Aplication\DTOs\MedicalData\DeleteMedicalDataDTO;
use App\Modules\Patients\Aplication\Exceptions\MedicalData\MedicalDataAplicationExceptions;
use App\Modules\Patients\Domain\Service\MedicalDataService;
use App\Modules\Patients\Domain\ValueObjects\Patients\PatientId;

final readonly class DeleteMedicalDataByPatientIdUseCase
{
    public function __construct(
        private MedicalDataService $medicalDataService,
    ) {}

    public function execute(DeleteMedicalDataDTO $dto): void
    {
        if ($dto->patientId === '') {
            throw MedicalDataAplicationExceptions::IdNotProvided();
        }

        $this->medicalDataService->deleteMedicalData(new PatientId($dto->patientId));
    }
}
