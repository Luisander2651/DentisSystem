<?php

declare(strict_types=1);

namespace App\Modules\Patients\Aplication\UseCases;

use App\Modules\Patients\Domain\Service\PatientService;
use App\Modules\Patients\Domain\ValueObjects\Patients\PatientId;
use App\Modules\Patients\Aplication\Exceptions\PatientAplicationExceptions;

final readonly class GetPatientByIdUseCase
{
    public function __construct(
        private PatientService $patientService,
    ) {}

    public function execute(string $id)
    {
        if (empty($id)) {
            throw PatientAplicationExceptions::IdNotProvided();
        }

        $patientId = new PatientId($id);
        return $this->patientService->findById($patientId);
    }
}