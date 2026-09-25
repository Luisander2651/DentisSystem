<?php

declare(strict_types=1);

namespace App\Modules\Patients\Aplication\UseCases;

use App\Core\Authorization\AuthorizationServiceInterface;
use App\Modules\Patients\Aplication\DTOs\GetPatientsByStatusDTO;
use App\Modules\Patients\Domain\Entities\Patient;
use App\Modules\Patients\Domain\Service\PatientService;
use App\Modules\Patients\Domain\ValueObjects\Patients\PatientStatus;

final readonly class GetPatientsByStatusUseCase
{
    public function __construct(
        private AuthorizationServiceInterface $authorization,
        private PatientService $patientService,
    ) {}

    /**
     * @return Patient[]
     */
    public function execute(GetPatientsByStatusDTO $dto): array
    {
        $this->authorization->assertCan('patients.view');

        $status = null;

        if ($dto->status !== null) {
            $status = PatientStatus::fromString($dto->status);
        }

        return $this->patientService->findByRoleAndStatus($status);
    }
}
