<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Aplication\UseCases;

use App\Core\Authorization\AuthorizationServiceInterface;
use App\Core\Domain\Pagination\Page;
use App\Core\Domain\Pagination\PageRequest;
use App\Modules\Appointments\Aplication\DTOs\ListTreatmentsPageDTO;
use App\Modules\Appointments\Domain\Entities\TreatmentEntity;
use App\Modules\Appointments\Domain\Service\TreatmentsService;

/**
 * Spec 028 (CA5, CA14; P5): one page of the treatments with the stats of the whole catalogue,
 * which ignore the search.
 */
final readonly class ListTreatmentsPageUseCase
{
    public function __construct(
        private TreatmentsService $treatmentsService,
        private AuthorizationServiceInterface $authorization,
    ) {}

    /**
     * @return array{page: Page<TreatmentEntity>, counts: array{total: int, with_duration: int, average_minutes: int|null}}
     */
    public function execute(ListTreatmentsPageDTO $dto): array
    {
        $this->authorization->assertCan('treatments.view');

        return [
            'page' => $this->treatmentsService->findPage(PageRequest::create($dto->page, $dto->perPage), $dto->search),
            'counts' => $this->treatmentsService->stats(),
        ];
    }
}
