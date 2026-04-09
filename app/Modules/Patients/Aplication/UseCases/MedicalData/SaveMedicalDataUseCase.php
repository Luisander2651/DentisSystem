<?php

declare(strict_types=1);

namespace App\Modules\Patients\Aplication\UseCases\MedicalData;

use App\Modules\Patients\Aplication\DTOs\MedicalData\CreateMedicalDataDTO;
use App\Modules\Patients\Aplication\Exceptions\MedicalData\MedicalDataAplicationExceptions;
use App\Modules\Patients\Domain\Entities\MedicalData;
use App\Modules\Patients\Domain\Repositories\MedicalDataRepositoryInterface;
use App\Modules\Patients\Domain\Service\MedicalDataService;
use App\Modules\Patients\Domain\ValueObjects\MedicalData\Allergies;
use App\Modules\Patients\Domain\ValueObjects\MedicalData\BloodType;
use App\Modules\Patients\Domain\ValueObjects\MedicalData\LastDentistVisit;
use App\Modules\Patients\Domain\ValueObjects\MedicalData\Medications;
use App\Modules\Patients\Domain\ValueObjects\MedicalData\MedicalDataPatientId;
use App\Modules\Patients\Domain\ValueObjects\Patients\PatientId;

final readonly class SaveMedicalDataUseCase
{
    public function __construct(
        private MedicalDataRepositoryInterface $medicalDataRepository,
        private MedicalDataService $medicalDataService,
    ) {}

    public function execute(CreateMedicalDataDTO $dto): void
    {
        if ($dto->patientId === '') {
            throw MedicalDataAplicationExceptions::IdNotProvided();
        }

        $existingMedicalData = $this->medicalDataRepository->findByPatientId(new PatientId($dto->patientId));

        if ($existingMedicalData !== null) {
            throw MedicalDataAplicationExceptions::AlreadyExists();
        }

        $medicalData = MedicalData::create(
            patientId: new MedicalDataPatientId($dto->patientId),
            bloodType: BloodType::fromNullable($dto->bloodType),
            allergies: Allergies::fromNullableArray($dto->allergies),
            medications: Medications::fromNullableArray($dto->medications),
            lastDentistVisit: LastDentistVisit::fromNullableArray($dto->lastDentistVisit),
        );

        $this->medicalDataService->saveOrUpdateMedicalData($medicalData);
    }
}
