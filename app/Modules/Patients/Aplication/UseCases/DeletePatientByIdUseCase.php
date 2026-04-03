<?php

declare(strict_types=1);

namespace App\Modules\Patients\Aplication\UseCases;

use App\Core\Authorization\AuthorizationServiceInterface;
use App\Modules\Patients\Domain\Service\PatientService;
use App\Modules\Patients\Domain\ValueObjects\Patients\PatientId;

final readonly class DeletePatientByIdUseCase
{
    public function __construct(
        private AuthorizationServiceInterface $authorization,
        private PatientService $patientService,
    ) {}

    public function execute(string $id): void
    {
        $this->authorization->assertCan('patients.delete');

        $patientId = new PatientId($id);
        $this->patientService->deleteById($patientId);
    }
}
