<?php

declare(strict_types=1);

namespace App\Modules\Patients\Aplication\UseCases;

use App\Modules\Patients\Aplication\DTOs\UpdatePatientDTO;
use App\Modules\Patients\Aplication\Exceptions\PatientAplicationExceptions;
use App\Modules\Patients\Domain\Service\PatientService;
use App\Modules\Patients\Domain\ValueObjects\Patients\PasswordHash;
use App\Modules\Patients\Domain\ValueObjects\Patients\PatientId;

final readonly class UpdatePatientUseCase
{
    public function __construct(
        private PatientService $patientService,
    ) {}

    public function execute(string $id, UpdatePatientDTO $dto): void
    {
        if (!$dto->hasValue()) {
            throw PatientAplicationExceptions::NoInfoProvided();
        }

        $patient = $this->patientService->findById(new PatientId($id));

        $patient->update(
            firstName: $dto->firstName,
            lastName: $dto->lastName,
            patientStatus: $dto->status,
        );

        if ($dto->newPassword !== null) {
            $patient->changePassword(PasswordHash::createFromPlainText($dto->newPassword));
        }

        $this->patientService->updatePatient($patient);
    }
}
