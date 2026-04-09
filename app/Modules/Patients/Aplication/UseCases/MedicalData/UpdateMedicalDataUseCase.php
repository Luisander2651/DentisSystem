<?php

declare(strict_types=1);

namespace App\Modules\Patients\Aplication\UseCases\MedicalData;

use App\Modules\Patients\Aplication\DTOs\MedicalData\UpdateMedicalDataDTO;
use App\Modules\Patients\Aplication\Exceptions\MedicalData\MedicalDataAplicationExceptions;
use App\Modules\Patients\Domain\Repositories\MedicalDataRepositoryInterface;
use App\Modules\Patients\Domain\Service\MedicalDataService;
use App\Modules\Patients\Domain\ValueObjects\MedicalData\Allergies;
use App\Modules\Patients\Domain\ValueObjects\MedicalData\BloodType;
use App\Modules\Patients\Domain\ValueObjects\MedicalData\LastDentistVisit;
use App\Modules\Patients\Domain\ValueObjects\MedicalData\Medications;
use App\Modules\Patients\Domain\ValueObjects\Patients\PatientId;

final readonly class UpdateMedicalDataUseCase
{
    public function __construct(
        private MedicalDataRepositoryInterface $medicalDataRepository,
        private MedicalDataService $medicalDataService,
    ) {}

    public function execute(string $patientId, UpdateMedicalDataDTO $dto): void
    {
        if ($patientId === '') {
            throw MedicalDataAplicationExceptions::IdNotProvided();
        }

        if (!$dto->hasValue()) {
            throw MedicalDataAplicationExceptions::NoInfoProvided();
        }

        $medicalData = $this->medicalDataRepository->findByPatientId(new PatientId($patientId));

        if ($medicalData === null) {
            throw MedicalDataAplicationExceptions::NotFound();
        }

        $medicalData->update(
            bloodType: $dto->bloodType !== null ? BloodType::fromNullable($dto->bloodType) : null,
            allergies: $dto->allergies !== null ? Allergies::fromNullableArray($dto->allergies) : null,
            medications: $dto->medications !== null ? Medications::fromNullableArray($dto->medications) : null,
            lastDentistVisit: $dto->lastDentistVisit !== null ? LastDentistVisit::fromNullableArray($dto->lastDentistVisit) : null,
        );

        $this->medicalDataService->saveOrUpdateMedicalData($medicalData);
    }
}
