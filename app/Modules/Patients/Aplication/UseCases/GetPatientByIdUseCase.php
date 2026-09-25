<?php

declare(strict_types=1);

namespace App\Modules\Patients\Aplication\UseCases;

use App\Core\Authorization\AuthorizationServiceInterface;
use App\Modules\Patients\Aplication\Exceptions\PatientAplicationExceptions;
use App\Modules\Patients\Domain\Service\PatientService;
use App\Modules\Patients\Domain\ValueObjects\Patients\PatientId;

final readonly class GetPatientByIdUseCase
{
    public function __construct(
        private AuthorizationServiceInterface $authorization,
        private PatientService $patientService,
    ) {}

    public function execute(string $id)
    {
        $this->authorization->assertCan('patients.view');

        if (empty($id)) {
            throw PatientAplicationExceptions::IdNotProvided();
        }

        $patientId = new PatientId($id);

        return $this->patientService->findById($patientId);
    }
}
