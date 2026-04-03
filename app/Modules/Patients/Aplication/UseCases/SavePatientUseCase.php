<?php

declare(strict_types=1);

namespace App\Modules\Patients\Aplication\UseCases;

use App\Core\Authorization\AuthorizationServiceInterface;
use App\Modules\Patients\Aplication\DTOs\CreatePatientDTO;
use App\Modules\Patients\Domain\Entities\Patient;
use App\Modules\Patients\Domain\Service\PatientService;
use App\Modules\Patients\Domain\ValueObjects\Patients\PatientEmail;
use App\Modules\Patients\Domain\ValueObjects\Patients\PatientName;
use App\Modules\Patients\Domain\ValueObjects\Patients\PasswordHash;

final readonly class SavePatientUseCase
{
    public function __construct(
        private AuthorizationServiceInterface $authorization,
        private PatientService $patientService,
    ) {}

    public function execute(CreatePatientDTO $dto): void
    {
        $this->authorization->assertCan('patients.create');

        $patient = Patient::create(
            name: PatientName::create($dto->firstName, $dto->lastName),
            email: new PatientEmail($dto->email),
            passwordHash: PasswordHash::createFromPlainText($dto->password),
        );

        $this->patientService->savePatient($patient);
    }
}
