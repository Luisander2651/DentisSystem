<?php

declare(strict_types=1);

namespace App\Modules\Patients\Aplication\UseCases;

use App\Modules\Patients\Aplication\DTOs\GetPatientsByStatusDTO;
use App\Modules\Patients\Domain\Entities\Patient;
use App\Modules\Patients\Domain\Service\PatientService;
use App\Modules\Patients\Domain\ValueObjects\Patients\PatientStatus;

final readonly class GetPatientsByStatusUseCase
{
    public function __construct(
        private PatientService $patientService,
    ) {}

    /**
     * @return Patient[]
     */
    public function execute(GetPatientsByStatusDTO $dto): array
    {
        $status = null;

        if ($dto->status !== null) {
            $status = PatientStatus::fromString($dto->status);
        }

        return $this->patientService->findByRoleAndStatus($status);
    }
}
