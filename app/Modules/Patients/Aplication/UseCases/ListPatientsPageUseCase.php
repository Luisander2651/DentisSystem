<?php

declare(strict_types=1);

namespace App\Modules\Patients\Aplication\UseCases;

use App\Core\Authorization\AuthorizationServiceInterface;
use App\Core\Domain\Pagination\Page;
use App\Core\Domain\Pagination\PageRequest;
use App\Modules\Patients\Aplication\DTOs\ListPatientsPageDTO;
use App\Modules\Patients\Domain\Entities\Patient;
use App\Modules\Patients\Domain\Service\PatientService;
use App\Modules\Patients\Domain\ValueObjects\Patients\PatientStatus;

/**
 * Spec 028 (CA5, CA14; P5): one page of the patients with the counts of the whole list, which
 * ignore the search and the status filter.
 */
final readonly class ListPatientsPageUseCase
{
    public function __construct(
        private AuthorizationServiceInterface $authorization,
        private PatientService $patientService,
    ) {}

    /**
     * @return array{page: Page<Patient>, counts: array{total: int, active: int, inactive: int}}
     */
    public function execute(ListPatientsPageDTO $dto): array
    {
        $this->authorization->assertCan('patients.view');

        $status = $dto->status === null ? null : PatientStatus::fromString($dto->status);

        return [
            'page' => $this->patientService->findPage(PageRequest::create($dto->page, $dto->perPage), $status, $dto->search),
            'counts' => $this->patientService->countByStatus(),
        ];
    }
}
